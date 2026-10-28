"""Demo installer offline checks. Does not claim a WordPress end-to-end run."""
from pathlib import Path
from PIL import Image
import unittest, json, subprocess
ROOT=Path(__file__).resolve().parents[1]
THEME=ROOT/'wordpress-theme/bangkok-guru'
PLUGIN=ROOT/'wordpress-plugin/bangkok-guru-bookings'
class DemoReleaseTests(unittest.TestCase):
 def setUp(self):
  self.data=json.loads((THEME/'demo-data/content.json').read_text(encoding='utf8'))
 def test_four_homepages_and_navigator(self):
  self.assertEqual(set(self.data['homepages']),{'travel','daylight','immersive','departures'})
  s=(THEME/'inc/demo.php').read_text();self.assertIn("bg_demo_build_nav",s)
 def test_eighteen_sample_entries(self):
  types=[x['type'] for x in self.data['items']]
  for t in ['bg_journey','bg_destination','bg_experience']:
   self.assertEqual(types.count(t),6)
  self.assertEqual(len({x['slug'] for x in self.data['items']}),18)
 def test_demo_prices_and_dates_are_sensible(self):
  for e in self.data['items']:
   self.assertGreater(len(e['subtitle']),55)
   self.assertGreater(len(e['highlights']),2)
   self.assertGreater(len(e['route']),2)
   if e['type']=='bg_journey': self.assertGreater(e['price'],0);self.assertGreater(len(e['days']),2)
   if e['type']=='bg_destination': self.assertEqual(e['days'],[])
   for offset in e['days']: self.assertGreaterEqual(offset,7)
 def test_demo_original_images(self):
  for e in self.data['items']:
   path=THEME/'assets/demo'/f"{e['cover']}.jpg"
   self.assertTrue(path.exists(),str(path))
   with Image.open(path) as img:
    self.assertEqual(img.size,(1200,780));img.verify()
 def test_demo_editorial_pages(self):
  self.assertGreaterEqual(len(self.data['pages']),5)
  self.assertEqual(len(self.data['stories']),3)
  for p in self.data['pages']:
   self.assertGreater(len(p['content']),100)
 def test_admin_opt_in_and_nonce(self):
  s=(THEME/'inc/demo.php').read_text()
  for needle in ('Yes — Install My Demo Website','No thanks','bg_demo_skip','check_admin_referer','current_user_can','bg_demo_install_plugin','bg_demo_seed_site','after_switch_theme','admin_notices','bg_demo_wizard_dismissed'):
   self.assertIn(needle,s)
  self.assertNotIn('add_action(\'after_switch_theme\',\'bg_demo_seed_site\')',s)
 def test_idempotent_and_privacy(self):
  s=(THEME/'inc/demo.php').read_text()
  for needle in ("if($id && get_post($id))return $id", "'_bg_demo_managed'", "'post_status'=>'draft'", "update_option('page_on_front'", 'bg_demo_build_nav'):
   self.assertIn(needle,s)
 def test_demo_booking_block_in_front_and_server(self):
  s=(PLUGIN/'includes/forms.php').read_text()
  self.assertGreaterEqual(s.count("get_post_meta($id,'_bg_demo_item',true)==='1'")+s.count("get_post_meta($entry_id,'_bg_demo_item',true)==='1'"),2)
  self.assertIn("bg_form_error('This is a demo itinerary",s)
  self.assertIn("Reservations and online payments are disabled",s)
 def test_live_requires_positive_price_and_departure(self):
  s=(PLUGIN/'includes/content.php').read_text()
  for needle in ('bg_demo_enable_live','_bg_demo_item',"get_post_meta($post_id,'_bg_price',true)>0",'has_future'):
   self.assertIn(needle,s)
 def test_brand_and_footer_credit(self):
  s=(THEME/'footer.php').read_text()
  self.assertIn('Min SiThu (itsmeminsithu)',s);self.assertIn('https://minsithu.org',s)
  self.assertTrue((THEME/'assets/brand/bangkok-guru-logo-light.png').exists())
 def test_terminal_demo_and_publish_docs(self):
  self.assertTrue((ROOT/'scripts/import-demo.sh').exists())
  self.assertTrue((ROOT/'docs/DEMO_SETUP_MY.md').exists())
  self.assertTrue((ROOT/'docs/DEMO_PUBLISH_CHECKLIST.md').exists())
 def test_php_and_shell_syntax(self):
  for path in list(THEME.rglob('*.php'))+list(PLUGIN.rglob('*.php')):
   r=subprocess.run(['php','-l',str(path)],capture_output=True,text=True)
   self.assertEqual(r.returncode,0,f'{path}: {r.stderr}')
  p=subprocess.run(['bash','-n',str(ROOT/'scripts/import-demo.sh')],capture_output=True,text=True)
  self.assertEqual(p.returncode,0,p.stderr)

if __name__=='__main__':unittest.main(verbosity=2)
