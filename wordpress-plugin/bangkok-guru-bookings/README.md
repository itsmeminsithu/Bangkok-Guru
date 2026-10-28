# Bangkok Guru Bookings — 0.12

Independent WordPress plugin for tour inventory and requests. **By Min SiThu (itsmeminsithu)**.

**Install the Bangkok Guru v0.12 theme FIRST** when upgrading from v0.11. Otherwise activate plugin under a different theme (it still registers the custom content types and shortcodes).

Shortcodes: `[bangkok_guru_trip_search]`, `[bangkok_guru_my_bookings]`, `[bangkok_guru_booking id="123"]`, `[bangkok_guru_reviews id="123"]`, `[bangkok_guru_enquiry]`, `[bangkok_guru_languages]`.

My Bookings and Find Trips pages are created on activation. Use Settings → Permalinks → Save Changes if routes fail. WooCommerce is optional; activate separately for payments. All financial operations require further real sandbox verification before handling real customer money.

Customer bookings from the old theme do not automatically gain a logged-in user ID. Old guest bookings can be viewed through an email-verified private link. For Verified Review posting of old bookings, identity must be reconciled by site administrator.

## v0.13.0
- Demo sample bookings are blocked at both the UI and form submission layers.
- Admin can explicitly mark a revised sample entry live only with a positive price and a valid future departure.
- Demo departure list labels indicate sample/non-bookable dates.
