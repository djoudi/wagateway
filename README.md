# نظام WaGateway — بوابة WhatsApp API

منصة SaaS متكاملة لإرسال واستقبال رسائل WhatsApp عبر API احترافي، مبنية على Laravel 13 + PHP 8.5 + Node.js.

---

## المكدس التقني

| الطبقة | التقنية |
|---|---|
| Backend | Laravel 13 · PHP 8.5 |
| Frontend | Livewire 3 · Alpine.js · Tailwind CSS v4 |
| Admin Panel | Filament v3 |
| WA Engine | whatsapp-web.js (Node.js 20) |
| Database | PostgreSQL 17 |
| Cache / Queue | Redis 7 |
| WebSocket | Laravel Reverb |
| Queue Monitor | Laravel Horizon |
| Containers | Docker + Docker Compose |

---

## متطلبات الخادم

| المورد | الحد الأدنى | الموصى به |
|---|---|---|
| vCPU | 2 | 4 |
| RAM | 4 GB | 8 GB |
| تخزين | 20 GB SSD | 50 GB SSD |
| نظام التشغيل | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |

> **ملاحظة:** كل جلسة WhatsApp تستهلك ~200 MB RAM. خادم بـ 8 GB يدعم ~20 جهازاً متزامناً بسهولة.

---

## التثبيت والنشر

### 1. إعداد الخادم

```bash
# تحديث النظام
sudo apt update && sudo apt upgrade -y

# تثبيت Docker
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# تثبيت Docker Compose
sudo apt install docker-compose-plugin -y
```

### 2. استنساخ المشروع

```bash
git clone https://github.com/yourname/wagateway.git
cd wagateway
```

### 3. إعداد متغيرات البيئة

```bash
cp .env.example .env
nano .env
```

**المتغيرات الإلزامية:**

```env
APP_KEY=                          # php artisan key:generate
APP_URL=https://yourdomain.com

DB_PASSWORD=STRONG_PASSWORD_HERE
REDIS_PASSWORD=STRONG_REDIS_PASS

WA_SERVICE_SECRET=MIN_32_CHARS_SECRET_KEY

ADMIN_EMAILS=admin@yourdomain.com

REVERB_APP_KEY=random_key
REVERB_APP_SECRET=random_secret
```

### 4. توليد APP_KEY

```bash
docker run --rm php:8.5-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

انسخ الناتج إلى `APP_KEY=` في ملف `.env`.

### 5. تشغيل الخدمات

```bash
# بناء وتشغيل جميع الحاويات
docker compose up -d --build

# التحقق من حالة الخدمات
docker compose ps
```

يجب أن تظهر 7 خدمات في حالة `running`:
- `wg_app` — Laravel application
- `wg_nginx` — Nginx reverse proxy
- `wg_wa_service` — Node.js WhatsApp engine
- `wg_postgres` — PostgreSQL database
- `wg_redis` — Redis cache/queue
- `wg_horizon` — Queue workers
- `wg_reverb` — WebSocket server
- `wg_scheduler` — Cron scheduler

### 6. تهيئة قاعدة البيانات

```bash
# تشغيل migrations
docker exec wg_app php artisan migrate --force

# إضافة الخطط الافتراضية
docker exec wg_app php artisan db:seed --class=PlanSeeder

# توليد API keys لأول مستخدم (بعد التسجيل)
docker exec wg_app php artisan user:generate-api-key admin@yourdomain.com
```

### 7. إعداد SSL (مُوصى به بشدة)

```bash
# تثبيت Certbot
sudo apt install certbot -y

# الحصول على شهادة SSL
sudo certbot certonly --standalone -d yourdomain.com

# نسخ الشهادات
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem docker/ssl/cert.pem
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem   docker/ssl/key.pem
```

### 8. النشر على EasyPanel (حاوية واحدة)

سهّل EasyPanel النشر عبر **حاوية واحدة شاملة** تحتوي PHP-FPM + Nginx + Reverb + Horizon + Scheduler + wa-service، وتُدار بواسطة Supervisor:

```bash
# النشر على EasyPanel يستخدم docker-compose.easypanel.yml (الحاوية الشاملة)
docker compose -f docker-compose.easypanel.yml up -d --build
```

> ملاحظة: المسار متعدد الخدمات القديم (`docker/Dockerfile.app` + `docker-compose.yml`) ما زال متاحاً للنشر الكلاسيكي على خادم VPS عبر `deploy.sh`، لكن **النشر الموصى به على EasyPanel هو الحاوية الشاملة**.

**الخدمات في هذا النموذج:**
- `app` — الحاوية الشاملة (تعرض المنفذ `80`، تُبنى من `Dockerfile`)
- `postgres` — قاعدة البيانات
- `redis` — كاش/قوائم الانتظار

**المتغيرات المطلوبة في منصة EasyPanel:** `APP_KEY`, `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `REDIS_PASSWORD`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_APP_ID`, `WA_SERVICE_SECRET`, `ADMIN_EMAILS`.

**ملاحظات النشر على EasyPanel:**
- Traefik ينهي SSL عند الحافة ويرسل HTTP داخلياً — لا حاجة لكتلة HTTPS في nginx.
- لوحة التحكم على `/dashboard` (وليس `/app`) لتجنب تعارض مسار Reverb (`/app/*` محجوز للـ WebSocket).
- إعدادات Reverb للواجهة تُحقن وقت التشغيل من Blade عبر `window.WaGatewayConfig` — لا حاجة لمتغيرات `VITE_*` وقت البناء.
- أصول Vite تُبنى **داخل** الصورة (`npm ci && npm run build`) — لا تعتمد على `public/build` من المضيف.
- healthcheck يستخدم `GET /up` (مسجّل تلقائياً في Laravel).

---

## هيكل الـ API

### Authentication

جميع طلبات API تتطلب مفتاح API في الـ Header:

```
Authorization: Bearer wg_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### نقاط النهاية الأساسية

```
# Health check (public)
GET  /api/health

# Devices
GET    /api/v1/devices
POST   /api/v1/devices
GET    /api/v1/devices/{id}
GET    /api/v1/devices/{id}/qr
POST   /api/v1/devices/{id}/reconnect
DELETE /api/v1/devices/{id}

# Messages
POST /api/v1/messages/send/text
POST /api/v1/messages/send/image
POST /api/v1/messages/send/document
POST /api/v1/messages/send/location
GET  /api/v1/messages
GET  /api/v1/messages/{id}

# Bulk (Pro+)
POST   /api/v1/messages/bulk
GET    /api/v1/messages/bulk/{id}
DELETE /api/v1/messages/bulk/{id}

# Scheduling (Pro+)
POST   /api/v1/messages/schedule
GET    /api/v1/messages/schedule
DELETE /api/v1/messages/schedule/{id}

# Webhooks
GET    /api/v1/webhooks
POST   /api/v1/webhooks
PATCH  /api/v1/webhooks/{id}
DELETE /api/v1/webhooks/{id}

# Templates
GET    /api/v1/templates
POST   /api/v1/templates
PUT    /api/v1/templates/{id}
DELETE /api/v1/templates/{id}
```

### مثال — إرسال رسالة نصية

```bash
curl -X POST https://yourdomain.com/api/v1/messages/send/text \
  -H "Authorization: Bearer wg_live_..." \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "uuid-xxxx",
    "to": "213700000001",
    "body": "مرحبا! هذه رسالة اختبارية."
  }'
```

**الرد:**

```json
{
  "success": true,
  "data": {
    "id": "msg-uuid-xxxx",
    "to": "213700000001",
    "type": "text",
    "status": "sent",
    "wa_id": "3EB0...",
    "sent_at": "2025-06-24T10:42:00Z"
  }
}
```

---

## خطط الاشتراك

| الميزة | Starter | Pro | Business |
|---|---|---|---|
| السعر الشهري | 500 DZD | 1,500 DZD | 2,500 DZD |
| رسائل/يوم | 1,000 | 10,000 | 100,000 |
| أجهزة | 2 | 10 | 30 |
| Webhooks | 3 | 10 | 30 |
| Bulk send | ❌ | ✅ | ✅ |
| Scheduling | ❌ | ✅ | ✅ |
| Templates | ✅ | ✅ | ✅ |

---

## إدارة المنصة (Filament Admin)

الوصول إلى لوحة الإدارة عبر: `https://yourdomain.com/admin`

أضف بريدك الإلكتروني في `.env`:

```env
ADMIN_EMAILS=admin@yourdomain.com,other@yourdomain.com
```

**إمكانيات الـ Admin Panel:**
- إدارة المستخدمين والاشتراكات
- تعليق/تفعيل الحسابات
- مراقبة الرسائل على مستوى المنصة
- إدارة الخطط والأسعار
- إحصائيات فورية

---

## التشخيص والمراقبة

```bash
# مراقبة الـ Queue workers
docker exec wg_app php artisan horizon

# التحقق من صحة WA Service
curl http://localhost:3000/health \
  -H "X-WG-Secret: your_wa_service_secret"

# سجلات اللحظة الفعلية
docker logs wg_wa_service -f
docker logs wg_horizon -f
docker logs wg_app -f

# إعادة تشغيل خدمة واحدة
docker compose restart wa-service

# فحص الأجهزة المتصلة
docker exec wg_app php artisan tinker
>>> App\Models\Device::where('status','connected')->count()
```

---

## تشغيل الاختبارات

```bash
# تثبيت Pest
docker exec wg_app composer require pestphp/pest --dev

# تشغيل جميع الاختبارات
docker exec wg_app php artisan test

# تشغيل مجموعة محددة
docker exec wg_app php artisan test --filter=AuthenticationTest
docker exec wg_app php artisan test --filter=DevicesTest
docker exec wg_app php artisan test --filter=MessagesTest

# مع تقرير التغطية
docker exec wg_app php artisan test --coverage
```

---

## الأمان والحوكمة

### نموذج الأمان — API Keys

**لا يوجد أي مفتاح API مخزّن كنص صريح في قاعدة البيانات.**

```
عند التوليد:  raw_key → SHA-256 hash → يُخزّن الـ hash فقط
عند العرض:    raw_key يُعرض مرة واحدة فقط، ثم يُفقد نهائياً
عند التحقق:   incoming_token → hash → مقارنة مع المخزّن
عند الفقدان:  لا استرجاع ممكن — إعادة توليد فقط (invalidates القديم فوراً)
```

اختراق قاعدة البيانات وحده **لا يكفي** لسرقة مفاتيح API صالحة — المهاجم يحصل على hashes غير قابلة للعكس.

### الحماية من Brute-Force

| المسار | الحد | آلية |
|---|---|---|
| `/login` | 5 محاولات/دقيقة لكل email+IP | `RateLimiter::for('auth')` |
| `/register` | 3 تسجيلات/دقيقة لكل IP | Named limiter |
| API auth (مفتاح خاطئ) | 20 محاولة/10 دقائق لكل IP | حظر تلقائي 429 |
| API عام | 300 طلب/دقيقة | حسب المستخدم أو IP |

### تشفير البيانات الحساسة

- **`devices.session_data_enc`** — مشفّر بـ Laravel `encrypt()` (AES-256-CBC عبر `APP_KEY`)
- **Webhook payloads** — موقّعة بـ HMAC-SHA256، `X-WG-Signature` قابل للتحقق من طرف العميل
- **Internal endpoint** (`/internal/wa-events`) — مقارنة `hash_equals()` مقاومة لـ timing attacks

### سجل التدقيق الأمني (Audit Trail)

جدول `security_events` يسجّل تلقائياً: `login_success · login_failed · api_key_invalid · api_key_regenerated`

- مرئي في لوحة الإدارة (`/admin` → Security Events) مع polling كل 30 ثانية
- فحص تلقائي كل ساعة (`security:audit-check`) يكشف credential stuffing وapi key enumeration
- ضروري لأي عميل مؤسسي/حكومي يطلب إثبات الحوكمة الأمنية

### Idempotency على أحداث WhatsApp

معالج `message_ack` لا يسمح بتراجع الحالة (مثال: `read` لا يتراجع إلى `delivered`)، ومعالج `message_received` يتجاهل التكرار بناءً على `wa_message_id` — يحمي من double-processing عند إعادة إرسال الأحداث من Node service.

### Rate Limiting المزدوج + فحص الملكية وقت التنفيذ

- **مستوى الخطة:** `RateLimitService` يفرض `daily_message_limit` عبر Redis counter
- **مستوى البنية التحتية:** Named limiters على `auth` و `api`
- **مستوى الـ Job:** `ProcessBulkJob` يتحقق من ملكية الجهاز عند التنفيذ الفعلي وليس فقط عند الإنشاء — يمنع استغلال تغيّر مالك الجهاز أثناء انتظار الطابور

### Anti-Ban للـ WhatsApp

- تأخير عشوائي بين رسائل Bulk (1-3 ثانية افتراضياً، قابل للتعديل)
- التحقق من صحة الأرقام قبل الإرسال
- حد يومي صارم مرتبط بالخطة
- جلسات معزولة لكل مستخدم (Docker volume منفصل)
- Auto-restart عند انقطاع الجلسة (3 محاولات، مع استثناء `LOGOUT`/`CONFLICT`)
- كشف Banned devices تلقائياً عبر Events + Webhook تنبيه فوري

### Headers أمنية (طبقتان)

مطبّقة في `nginx.conf` **و** `SecurityHeaders` middleware (دفاع مزدوج خلف load balancer بدون nginx المرفق):

```
Strict-Transport-Security · X-Frame-Options · X-Content-Type-Options
Referrer-Policy · Permissions-Policy · Cache-Control: no-store (على API)
```

### Soft Delete

الأجهزة محفوظة (`SoftDeletes`) قبل الحذف الفعلي — استرجاع + تتبع تاريخي كامل لأغراض التدقيق.

---

## هيكل المشروع

```
wagateway/
├── app/
│   ├── Console/Commands/        # Artisan commands
│   ├── Enums/                   # DeviceStatus, MessageStatus, etc.
│   ├── Events/                  # QrCodeGenerated, DeviceStatusChanged
│   ├── Filament/Resources/      # Admin panel resources
│   ├── Http/
│   │   ├── Controllers/Api/V1/  # REST API controllers
│   │   ├── Controllers/Internal/# Node.js event receiver
│   │   └── Middleware/          # ApiKeyAuthenticate, EnsurePlanFeature
│   ├── Jobs/                    # DeliverWebhook, ProcessBulkJob, etc.
│   ├── Livewire/                # All dashboard components
│   ├── Models/                  # Eloquent models
│   ├── Policies/                # Authorization policies
│   └── Services/                # WhatsApp, Webhook, RateLimit, Dispatcher
├── database/
│   ├── factories/               # Test factories
│   ├── migrations/              # 9 migrations
│   └── seeders/                 # PlanSeeder
├── resources/views/
│   ├── auth/                    # Login, Register
│   ├── components/              # nav-item
│   ├── layouts/                 # app.blade.php
│   └── livewire/                # All component views
├── routes/
│   ├── api.php                  # Public + authenticated API
│   ├── internal.php             # Node.js event endpoint
│   └── web.php                  # Dashboard routes
├── tests/Feature/Api/           # Pest feature tests
├── wa-service/                  # Node.js microservice
│   └── src/
│       ├── sessions/manager.js  # WhatsApp session management
│       ├── routes/              # Express routes
│       └── utils/               # Logger, notifier
├── docker/                      # Nginx config, Dockerfiles, supervisord, entrypoint
├── Dockerfile                   # Single all-in-one container (EasyPanel / default)
├── Dockerfile.single            # Alias of Dockerfile (same image)
├── docker-compose.easypanel.yml # EasyPanel compose (app + postgres + redis)
├── docker-compose.single.yml    # Compose for the single-container deploy
├── docker-compose.yml
└── .env.example
```

---

## الخطوات القادمة (Roadmap)

- [ ] دعم Meta Cloud API الرسمي (tier مميز)
- [ ] تطبيق موبايل لمسح QR Code
- [ ] تقارير PDF شهرية للمستخدمين
- [ ] دعم Chatbot بسيط (auto-reply)
- [ ] تكامل مع CRM شائعة (HubSpot, Zoho)
- [ ] نظام Reseller (باعة بالجملة)

---

## الترخيص

ملكية خاصة — جميع الحقوق محفوظة © 2025 WaGateway
