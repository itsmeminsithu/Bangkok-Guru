"""Source and package guardrails. Not a substitute for WordPress gateway E2E tests."""
from pathlib import Path
import re, subprocess, unittest, json
R=Path(__file__).resolve().parents[1]
T=R/'wordpress-theme/bangkok-guru'
P=R/'wordpress-plugin/bangkok-guru-bookings'
class BangkokGuruV012Checks(unittest.TestCase):
 def test_theme_install(self):
  for name in ('style.css','functions.php','index.php','front-page.php','header.php','screenshot.png','theme.json'):
   self.assertTrue((T/name).is_file(),name)
  self.assertIn('Version: 0.13.0',(T/'style.css').read_text())
  self.assertIn('Min SiThu',(T/'style.css').read_text())
 def test_plugin_install(self):
  self.assertIn('Plugin Name: Bangkok Guru Bookings',(P/'bangkok-guru-bookings.php').read_text())
  for name in ('content','booking','woocommerce','forms','privacy','filters','language','search','dashboard','reviews'):
   self.assertTrue((P/'includes'/f'{name}.php').is_file(),name)
 def test_theme_plugin_separation(self):
  bootstrap=(T/'functions.php').read_text()
  for name in ("'booking'","'woocommerce'","'content'","'forms'","'privacy'","'filters'"):
   self.assertNotIn(name,bootstrap)
  for needle in ('bg_theme_booking_form','bg_theme_enquiry_form','bg_theme_departures_board'):
   self.assertIn(needle,bootstrap)
  # Shared global PHP function names must not be declared in both independently installed packages.
  defs=lambda folder: {x for file in folder.rglob('*.php') for x in re.findall(r'(?m)^function\s+(bg[a-zA-Z0-9_]+)\s*\(',file.read_text())}
  self.assertFalse(defs(T)&defs(P),str(defs(T)&defs(P)))
 def test_migration_data_keys_preserved(self):
  b=(P/'includes/booking.php').read_text();c=(P/'includes/content.php').read_text()
  for key in ('bg_inventory','bg_request','_bg_reserved','_bg_departures','bg_journey','bg_destination','bg_experience'):
   self.assertIn(key,b+c)
  self.assertIn('bgx_active_theme',(P/'bangkok-guru-bookings.php').read_text())
 def test_search_guards(self):
  f=(P/'includes/filters.php').read_text()
  for txt in ('bg_from_date','bg_to_date','bg_seats','bg_available','wpdb->prepare','post__in','bgx_available_entry_ids'):
   self.assertIn(txt,f)
 def test_dashboard_auth(self):
  s=(P/'includes/dashboard.php').read_text()
  for txt in ('is_user_logged_in','get_current_user_id','_bg_customer_id','wp_verify_nonce','hash_equals','random_bytes','set_transient','nocache_headers','15*MINUTE_IN_SECONDS'):
   self.assertIn(txt,s)
  self.assertNotIn('GET[\'email\']',s)
 def test_review_verified(self):
  s=(P/'includes/reviews.php').read_text()
  for txt in ('bgx_review_eligible_booking','paid','confirmed','wp_date','pending','wp_verify_nonce','get_current_user_id','_bg_booking_id','aggregateRating'):
   self.assertIn(txt,s)
 def test_language(self):
  s=(P/'includes/language.php').read_text()
  for txt in ("'en'","'th'","'my'",'_bg_title_','bgx_local_meta','bgx_localized_entry_fields'):
   self.assertIn(txt,s)
 def test_woo_guards(self):
  s=(P/'includes/woocommerce.php').read_text()
  for txt in ('get_total()','get_currency()','_bg_woo_initial_order','released_or_stale_payment','manual_review','woocommerce_order_refunded'):
   self.assertIn(txt,s)
 def test_local_safe(self):
  for path in ('local/compose.yml','scripts/start-mac.sh','scripts/enable-theme.sh','scripts/sandbox-check.sh','tests/woo-local-smoke.php'):
   self.assertTrue((R/path).exists(),path)
  s=(R/'tests/woo-local-smoke.php').read_text()
  self.assertIn('BGX_SANDBOX',s);self.assertIn("wp_get_environment_type()!=='local'",s)
  self.assertIn('8188',(R/'local/.env.example').read_text())
 def test_brand_assets(self):
  for s in ('bangkok-guru-logo-light.png','bangkok-guru-logo-dark.png'):
   self.assertTrue((T/'assets/brand'/s).exists())
  self.assertEqual(len(json.loads((T/'theme.json').read_text())['settings']['color']['palette']),8)
 def test_source_lint(self):
  for file in list(T.rglob('*.php'))+list(P.rglob('*.php'))+(list((R/'tests').glob('*.php'))):
   p=subprocess.run(['php','-l',str(file)],capture_output=True,text=True)
   self.assertEqual(p.returncode,0,f'{file.name}: {p.stderr}')
 def test_mock_payment_suite(self):
  p=subprocess.run(['php',str(R/'tests/php_runtime.php')],capture_output=True,text=True)
  self.assertEqual(p.returncode,0,p.stderr+p.stdout)
  self.assertGreaterEqual(p.stdout.count('PASS'),25)
if __name__=='__main__':unittest.main(verbosity=2)
