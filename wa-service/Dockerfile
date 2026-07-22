FROM node:20-slim
RUN apt-get update && apt-get install -y chromium fonts-noto-color-emoji libatk-bridge2.0-0 libatk1.0-0 libcups2 libdrm2 libgbm1 libglib2.0-0 libnspr4 libnss3 libxcomposite1 libxdamage1 libxfixes3 libxkbcommon0 libxrandr2 xdg-utils --no-install-recommends && rm -rf /var/lib/apt/lists/*
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium
WORKDIR /app
COPY package*.json ./
# NOTE: no package-lock.json is committed yet. `npm ci` requires one to
# already exist and fails hard without it — using `npm install` here so
# the first build resolves and generates the lock fresh. After your first
# successful build, copy it out and commit it for reproducible builds:
#   docker cp wg_wa_service:/app/package-lock.json ./wa-service/package-lock.json
# Then switch this line back to `npm ci --omit=dev` for faster, locked installs.
RUN npm install --omit=dev
RUN mkdir -p sessions src/logs
COPY src/ ./src/
EXPOSE 3000
USER node
CMD ["node", "src/index.js"]
