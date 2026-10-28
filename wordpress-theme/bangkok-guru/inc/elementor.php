<?php
/** Four original Elementor widgets. Standard Content and Style tabs (no Pro required). */
if(!defined('ABSPATH'))exit;
function bg_elementor_register($widgets_manager){
    if(!class_exists('\\Elementor\\Widget_Base'))return;
    if(!class_exists('BG_Elementor_Base')){
       abstract class BG_Elementor_Base extends \Elementor\Widget_Base {
         protected function add_bg_style_controls(){
           $this->start_controls_section('bg_style',array('label'=>__('Design & Style','bangkok-guru'),'tab'=>\Elementor\Controls_Manager::TAB_STYLE));
           $this->add_control('bg_text_color',array('label'=>'Text color','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .bg-widget-root'=>'color: {{VALUE}};','{{WRAPPER}} .bg-widget-root h1'=>'color: {{VALUE}};')));
           $this->add_control('bg_bg_color',array('label'=>'Background','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .bg-widget-root'=>'background-color: {{VALUE}};')));
           $this->add_control('bg_radius',array('label'=>'Corner radius','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>array('px'),'range'=>array('px'=>array('min'=>0,'max'=>80)),'selectors'=>array('{{WRAPPER}} .bg-widget-root'=>'border-radius: {{SIZE}}px;overflow:hidden;')));
           $this->add_responsive_control('bg_padding',array('label'=>'Padding','type'=>\Elementor\Controls_Manager::DIMENSIONS,'size_units'=>array('px','%'),'selectors'=>array('{{WRAPPER}} .bg-widget-root'=>'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};')));
           $this->add_control('bg_title_typography',array('label'=>'Heading Typography','type'=>\Elementor\Controls_Manager::HEADING));
           $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),array('name'=>'bg_title_font','selector'=>'{{WRAPPER}} .bg-widget-root h1, {{WRAPPER}} .bg-widget-root h2'));
           $this->end_controls_section();
         }
       }
    }
    if(!class_exists('BG_Widget_Hero')){
      class BG_Widget_Hero extends BG_Elementor_Base{
        public function get_name(){return 'bg_hero';}
        public function get_title(){return 'Bangkok Guru · Hero';}
        public function get_icon(){return 'eicon-banner';}
        public function get_categories(){return array('general');}
        protected function register_controls(){
          $this->start_controls_section('bg_content',array('label'=>'Hero Content'));
          $this->add_control('eyebrow',array('label'=>'Eyebrow','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'EXPLORE THAILAND'));
          $this->add_control('headline',array('label'=>'Headline','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'Your next story starts here.'));
          $this->add_control('intro',array('label'=>'Intro','type'=>\Elementor\Controls_Manager::TEXTAREA,'default'=>'Thoughtful journeys, meaningful moments and beautiful places.'));
          $this->add_control('cta',array('label'=>'Button text','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'Explore journeys'));
          $this->add_control('cta_link',array('label'=>'Button link','type'=>\Elementor\Controls_Manager::URL,'default'=>array('url'=>'/journeys/')));
          $this->end_controls_section();$this->add_bg_style_controls();
        }
        protected function render(){
          $s=$this->get_settings_for_display();$url=!empty($s['cta_link']['url'])?$s['cta_link']['url']:get_post_type_archive_link('bg_journey');
          echo '<section class="bg-widget-root bg-widget-hero"><div class="bg-eyebrow">'.esc_html($s['eyebrow']).'</div><h1>'.esc_html($s['headline']).'</h1><p>'.esc_html($s['intro']).'</p><a class="bg-button" href="'.esc_url($url).'" '.(!empty($s['cta_link']['is_external'])?'target="_blank" rel="noopener noreferrer"':'').'>'.esc_html($s['cta']).' ↗</a></section>';
        }
      }
    }
    if(!class_exists('BG_Widget_Trips')){
      class BG_Widget_Trips extends BG_Elementor_Base{
        public function get_name(){return 'bg_trips';}
        public function get_title(){return 'Bangkok Guru · Travel Cards';}
        public function get_icon(){return 'eicon-gallery-grid';}
        public function get_categories(){return array('general');}
        protected function register_controls(){
          $this->start_controls_section('content',array('label'=>'Travel Cards'));
          $this->add_control('headline',array('label'=>'Heading','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'Discover something wonderful'));
          $this->add_control('kind',array('label'=>'Collection','type'=>\Elementor\Controls_Manager::SELECT,'options'=>array('bg_journey'=>'Journeys','bg_destination'=>'Destinations','bg_experience'=>'Day Experiences'),'default'=>'bg_journey'));
          $this->add_control('qty',array('label'=>'Number of cards','type'=>\Elementor\Controls_Manager::NUMBER,'min'=>1,'max'=>12,'default'=>3));
          $this->end_controls_section();$this->add_bg_style_controls();
        }
        protected function render(){$s=$this->get_settings_for_display();echo '<section class="bg-widget-root"><h2>'.esc_html($s['headline']).'</h2>';bg_cards($s['kind'],max(1,min(12,(int)$s['qty'])));echo '</section>';}
      }
    }
    if(!class_exists('BG_Widget_Departures')){
      class BG_Widget_Departures extends BG_Elementor_Base{
        public function get_name(){return 'bg_departures';}
        public function get_title(){return 'Bangkok Guru · Departures';}
        public function get_icon(){return 'eicon-calendar';}
        public function get_categories(){return array('general');}
        protected function register_controls(){
          $this->start_controls_section('content',array('label'=>'Upcoming Departures'));
          $this->add_control('headline',array('label'=>'Heading','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'Upcoming departures'));
          $this->add_control('qty',array('label'=>'Rows','type'=>\Elementor\Controls_Manager::NUMBER,'min'=>1,'max'=>20,'default'=>6));
          $this->end_controls_section();$this->add_bg_style_controls();
        }
        protected function render(){$s=$this->get_settings_for_display();echo '<section class="bg-widget-root"><h2>'.esc_html($s['headline']).'</h2>';bg_render_departures_board(max(1,min(20,(int)$s['qty'])));echo '</section>';}
      }
    }
    if(!class_exists('BG_Widget_Enquiry')){
      class BG_Widget_Enquiry extends BG_Elementor_Base{
        public function get_name(){return 'bg_enquiry';}
        public function get_title(){return 'Bangkok Guru · Enquiry';}
        public function get_icon(){return 'eicon-form-horizontal';}
        public function get_categories(){return array('general');}
        protected function register_controls(){
          $this->start_controls_section('content',array('label'=>'Enquiry Form'));
          $this->add_control('headline',array('label'=>'Heading','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'Plan something extraordinary'));
          $this->add_control('type',array('label'=>'Enquiry type','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'tailor','options'=>array('tailor'=>'Tailor-made','message'=>'General message')));
          $this->end_controls_section();$this->add_bg_style_controls();
        }
        protected function render(){$s=$this->get_settings_for_display();echo '<section class="bg-widget-root bg-widget-enquiry"><h2>'.esc_html($s['headline']).'</h2>';echo bg_theme_enquiry_form($s['type']);echo '</section>';}
      }
    }
    foreach(array('BG_Widget_Hero','BG_Widget_Trips','BG_Widget_Departures','BG_Widget_Enquiry') as $class){$widgets_manager->register(new $class());}
}
add_action('elementor/widgets/register','bg_elementor_register');
