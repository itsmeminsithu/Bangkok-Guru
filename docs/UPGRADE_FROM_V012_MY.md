# Bangkok Guru v0.12 → v0.13 on Mac

For the site already running at localhost:8188, **do not start a second Docker Compose project with a fresh database** if you want to retain its data. Keep the existing project directory name and local/.env.

1. Back up WordPress database and local project:

```bash
cd ~/Downloads/Bangkok-Guru-v0.12
# Save a database export before updating (requires working WordPress Docker stack):
docker compose --env-file local/.env -f local/compose.yml run --rm wpcli db export /var/www/html/bangkok-guru-before-v013.sql
```

2. Apply patch **into the same existing folder** (not into `/wp-content/`):

```bash
cd ~/Downloads/Bangkok-Guru-v0.12
unzip -o ~/Downloads/Bangkok-Guru-v0.13-Mac-Update-Patch.zip -d .
docker compose --env-file local/.env -f local/compose.yml restart wordpress
./scripts/enable-theme.sh
./scripts/import-demo.sh travel
```

3. Open `http://localhost:8188`. Check Appearance → Bangkok Guru Setup and the new Journeys/Destinations/Experiences pages. You can choose any of the four homepages later without deleting edited pages.

**Important:** The local Docker stack maps Theme/Plugin source as `:ro` inside WordPress. WordPress Dashboard won't be able to update those folders from ZIPs. Use the Mac folder patch workflow instead. Never run `docker compose down -v` unless you intend to erase the database volumes.

WordPress Dashboard upload workflow (Theme ZIP then plugin ZIP) is intended for normal writable WordPress hosting, not this read-only development setup.
