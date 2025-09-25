Laravel Realtime Chat (Groq + Soketi)
=====================================

Simple Laravel chat app with realtime broadcasting via Soketi (Pusher protocol) and optional AI replies powered by Groq.

Requirements
- PHP 8.x, Composer
- Node.js & npm
- Docker Desktop (for Soketi)

Getting started
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Environment (.env)
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

AI_PROVIDER=groq
GROQ_API_KEY=your_groq_key
GROQ_BASE_URL=https://api.groq.com/openai/v1
GROQ_MODEL=llama-3.3-70b-versatile
AI_VERIFY_SSL=false
```

Run Soketi (Docker)
```bash
docker run --name soketi --rm -p 6001:6001 \
  -e SOKETI_DEFAULT_APP_ID=app-id \
  -e SOKETI_DEFAULT_APP_KEY=app-key \
  -e SOKETI_DEFAULT_APP_SECRET=app-secret \
  quay.io/soketi/soketi:1.0-16-debian
```

Run the app
```bash
npm run dev
php -S 127.0.0.1:8000 -t public
```

Usage
- Normal chat: send a message in the room, delivered instantly via Soketi.
- AI chat: start a message with "@ai " to get an AI response.
- New joiners see recent history; live updates continue in realtime.

Scripts
```bash
# Quality
composer run qa:check   # pint --test + phpstan
composer run qa:fix     # pint + php-cs-fixer

# Tests
./vendor/bin/phpunit
```

Git basics
```bash
git init
git add -A && git commit -m "chore: initial"
git remote add origin <your-remote>
git push -u origin master
```

Notes
- Ensure Soketi is running and .env PUSHER_* matches the container values.
- After changing .env, run `php artisan config:clear`.
