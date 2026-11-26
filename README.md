# Collegia

Collegia is a campus community platform for Universitas Riau students. It is one place for the things that usually get scattered across group chats and Instagram: short posts, campus events, open collaborations, and volunteer calls.

Access is limited to `@student.unri.ac.id` Google accounts, so the people you see in the feed are actual students. Student organizations and communities can also log in with an email and password through a separate entrance, and their posts carry a "Komunitas" badge so it is clear who is speaking.

The app is built as a Laravel monolith with an Inertia + Vue frontend. There is no separate API to maintain: controllers return Inertia pages, and the few endpoints that need to return JSON (pagination, payment status) do so directly.

## Stack

| Layer | What is used |
| --- | --- |
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3 (Composition API), TypeScript, Inertia 2 |
| Styling | Tailwind CSS 4, shadcn-vue components (reka-ui) |
| Build | Vite 7 |
| Auth | Laravel Socialite (Google), session-based web auth |
| Payments | Midtrans Snap |
| File storage | Cloudflare R2 through the S3 driver, with a local public disk fallback |
| Public IDs | `vinkla/hashids` so URLs do not expose sequential database IDs |
| Tests | Pest 3 |
| Tooling | Pint, ESLint, Prettier |

## Roles

There are four roles, stored as an enum on `users.role`.

- **user** — a regular student. Can post, comment, create events (paid), create collaborations, express interest, and report content. Displayed by their name with any trailing digits trimmed (`clean_name`).
- **community** — a student organization or community account. Logs in with a password, does not pay for event creation, and is shown with a Komunitas badge. Community accounts are what the Explore page lists.
- **moderator** — can remove other people's comments and posts.
- **admin** — goes straight to the admin dashboard. Manages users, events, and reported content.

## Features

### Feed and posts
The home feed mixes posts, events, collaborations, and volunteer calls into one reverse-chronological list with filter tabs. The first page also shows two pinned events, and the feed loads more as you scroll while keeping your filter and scroll position in `sessionStorage`.

Posts support text up to 5,000 characters and an optional image up to 5 MB. Content is sanitized before saving, checked for spammy wording, and rejected if the same user posted the same thing within the last 10 minutes.

### Events
Events have a title, organizer, date, location, description, image, and an optional price (this is the event's own ticket price, not a platform fee). Event pages show who is interested, and logged-in users can mark themselves as interested.

Regular students pay **Rp 5.000** to publish an event. The event is created invisible and only becomes visible once Midtrans confirms payment. Events can also be promoted to the top of the event list at **Rp 5.000 per day**, with the duration taken from the promotion start and end dates.

### Collaborations and volunteers
A collaboration is a call for people: "we need 5 people for this", with a type of `collaboration` or `volunteer`, a description, dates, location, image, and number of participants needed. Others can express interest, and the count is shown on the card. Owners can edit or delete their own collaboration.

### Comments and notifications
Comments live on post detail pages and load 10 at a time. Commenting on someone else's post creates a notification for the post owner. Notifications have an unread counter and a mark-as-read endpoint, and there is a dedicated notifications page.

### Reporting and moderation
Posts, comments, events, and collaborations can all be reported with a reason. Reports land in separate admin queues where they can be dismissed or used as the basis for deleting the content. Admin pages also let you delete events in bulk, including a "delete all completed events" action.

### Profiles and explore
Each user has a public profile at `/user/{hashId}/{name}` with tabs for posts, comments, and events, all paginated. Users can write a short bio and choose whether their posts and comments are private. The Explore page lists community accounts and lets you search across users.

### Admin dashboard
The dashboard shows totals for users by role, posts, comments, events (visible and hidden), transactions by status, and reports, plus a monthly chart of successful transactions for a selected year. From there, admins can create users, change roles, delete users, browse and delete events, and review every report queue.

## How a few things work

**Routing and URLs.** Pages are Inertia responses from `routes/web.php`. IDs in public URLs are Hashids, decoded in the controller before querying, so a post link looks like `/user/post/Xk9aB2` rather than `/user/post/17`.

**Payments.** `TransactionController` builds a Midtrans Snap token server-side. The amount is hardcoded there and never read from the request, so a user cannot tamper with the price. Pending transactions within the last minute are reused instead of creating duplicates, and each Snap session expires after 10 minutes. Midtrans calls back to `POST /midtrans/webhook` (excluded from CSRF in `bootstrap/app.php`), which updates the transaction and makes the related event visible on success.

**Uploads.** Images are stored on the `r2` disk and the models return an `image_url` that checks R2 first, then the local `public` disk for older files.

**Throttling.** Write actions are rate limited: posts 10/min, comments 15/min, collaborations 5/min, reports and payments 5–10/min, bio updates 3/min.

## Getting started

You need PHP 8.2+, Composer, Node 18+, and a database. SQLite works out of the box and is what the default `.env.example` points at.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

Then start everything with one command:

```bash
composer dev
```

That runs the PHP server, the queue listener, and Vite together. If you prefer separate terminals, run `php artisan serve` and `npm run dev`.

### Environment variables

The defaults in `.env.example` are enough to boot the app, but these need real values for the parts that talk to the outside world:

```dotenv
# Google login (only @student.unri.ac.id accounts are accepted)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Midtrans (sandbox keys are fine for development)
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false

# Cloudflare R2 for image uploads
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=
R2_ENDPOINT=
R2_PUBLIC_URL=
```

Add `GOOGLE_REDIRECT_URI` to your Google OAuth client, and point the Midtrans payment notification URL at `{APP_URL}/midtrans/webhook`.

To get into the admin area, create the first admin directly:

```bash
php artisan tinker
>>> \App\Models\User::create([
...   'name' => 'Admin',
...   'email' => 'admin@student.unri.ac.id',
...   'password' => bcrypt('password'),
...   'role' => 'admin',
... ]);
```

After that, admins can create and manage other accounts from the dashboard.

### Useful commands

```bash
composer dev        # server + queue + vite
npm run build       # production frontend build
npm run build:ssr   # build with Inertia SSR bundles
npm run lint        # eslint --fix
npm run format      # prettier on resources/
php artisan test    # run the Pest suite
./vendor/bin/pint   # PHP code style
```

## Project layout

```
app/
  Http/Controllers/     page controllers (feed, events, collaborations, payments, admin)
  Http/Requests/        validation for posts, comments, events
  Http/Middleware/      auth/role handling, shared Inertia props
  Models/               User, Post, CommentModel, EventModel, Collaboration, Transaction, reports
  Helpers/              SecurityHelper (sanitizing, spam, duplicate hashes)
database/migrations/    schema, newest migration dated 2026-01
resources/js/
  pages/auth/           Google and community login screens
  pages/user/           feed, detail pages, create forms, profile, events, transactions
  pages/admin/          dashboard, users, events, report queues
  pages/settings/       profile, password, appearance
  components/           shadcn-vue based UI components
routes/web.php          all app routes
routes/auth.php         Google, community, and password flows
```

## Docker

A `Dockerfile` is included. It builds the frontend in a Node stage, copies the output into a PHP 8.2 FPM image, installs Composer dependencies without dev packages, and runs `php-fpm`. It expects the usual Laravel volume/permission setup for `storage` and `bootstrap/cache` and does not include a web server, so pair it with nginx or your host's PHP-FPM handling. There is no `docker-compose.yml` in the repo.

## Testing

```bash
php artisan test
```

The Pest suite covers authentication, settings, and the default feature/unit examples. Add cases for any new endpoints you introduce; the existing tests are the intended pattern to follow.

## License

This project started from the Laravel + Vue starter kit, which is released under the MIT license. The application code on top of it is part of the Collegia project.
