# Bangkok Guru — Demo Website Setup (မြန်မာ)

## WordPress Dashboard Installation

1. Appearance → Themes → Add New → Upload Theme မှာ `Bangkok-Guru-WordPress-Theme-v0.13.0.zip` တင်ပြီး Activate လုပ်ပါ။
2. Theme Activate လုပ်ပြီးတာနဲ့ `Bangkok Guru · Website Setup` Wizard ပေါ်ပါမယ်။ မပေါ်ရင် Appearance → Bangkok Guru Setup ကို သွားပါ။
3. Homepage ၄ မျိုးထဲက ကြိုက်တာကို ရွေးပါ။
4. `Yes — Install My Demo Website` နှိပ်ပါ။ Booking Plugin မရှိသေးရင် Theme ထဲပါသော Plugin ZIP ကို administrator permission ရှိမှ auto-install လုပ်ရန် ကြိုးစားပါမယ်။ Web Hosting မှာ file write permission မရှိရင် Booking Plugin ZIP ကို Plugins → Add New → Upload Plugin ကနေ သီးခြားတင်ပါ။
5. Home, Journeys, Destinations, Experiences, Contact, FAQ, About, Journal Pages တွေကို preview ကြည့်ပါ။
6. Edit Trip → Content, Cover Image, Highlights, Itinerary, Price, Dates, Available Seats ကို ပြင်ပါ။
7. Preview မဟုတ်တော့ဘဲ တကယ် Bookings လက်ခံမယ်ဆိုရင် Demo Preview checkbox မှာ confirmation လုပ်ပြီး Save လုပ်ပါ။ Verified operator details, accurate itinerary, real pricing, legal conditions, photos with usage rights, real availability တွေ ပြီးမှသာ enable လုပ်ပါ။
8. Privacy Policy page ကို Site Owner အချက်အလက်နဲ့ ပြင်ပြီး Publish လုပ်ပါ။ WordPress Settings → Privacy မှာ သတ်မှတ်ပါ။

## What is included

- 4 homepage choices and sample content; reimport doesn't overwrite edited posts.
- 6 Journeys, 6 Destinations, 6 Day Experiences with detailed travel examples.
- Sample future dates/places/indicative pricing for preview. **Demo booking forms and payment submissions are disabled server-side.**
- 12 original editorial illustrations stored inside theme and copied into Media Library for post thumbnails. These are not real travel photography. Replace them with your own licensed photos.
- About, Contact, Tailor-made, FAQ, Travel Journal pages, 3 editorial posts, navigation menu.
- Footer developer credit: Min SiThu (itsmeminsithu) → minsithu.org.

## Mac Terminal preview

Use the GitHub ZIP. Extract to `~/Downloads/Bangkok-Guru-v0.13`, run:

```bash
cd ~/Downloads/Bangkok-Guru-v0.13
./scripts/start-mac.sh
# complete WordPress browser installer if needed
./scripts/enable-theme.sh
./scripts/import-demo.sh travel
```

Website: http://localhost:8188; inbox: http://localhost:8189.

### Existing v0.12 local project

If you already have data in `~/Downloads/Bangkok-Guru-v0.12`, back up the folder and MariaDB before updating. Avoid `docker compose down -v` and don't create a new unrelated Docker project expecting the same database. The source folder mounted in local Docker Compose is read-only, so WordPress Dashboard ZIP updating is intentionally unavailable there; update source files from your Mac instead and keep the existing local/.env.

### Sharing to customers

`localhost:8188` is **only on your own Mac** and is not a public link. To share with customers, deploy to secure HTTPS WordPress hosting (ideally staging with password protection). Replace example itineraries/prices, configure business contact and privacy terms, verify all photos, inspect mobile layouts, and test booking/checkout under a sandbox gateway before opening real payments.
