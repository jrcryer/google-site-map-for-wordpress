<?php
/*
    Plugin Name: Google Sitemap
    Version:     1.0
    Plugin URI:  http://www.jamescryer.com
    Description: Generates a valid Google XML sitemap with a very simple admin interface
    Author:      James Cryer
    Author URI:  http://www.jamescryer.com
*/
class GoogleSiteMap {
    
    const PLUGIN_NAME = 'Google Sitemap';
    
    const ENABLED     = 'Enable';
    
    const INCLUDED     = 'Include';
    
    public function __construct() {}
    
    /**
     * Initialise plugin, add hooks for various functions
     */
    public function initialise() {
        add_action('admin_menu', array(&$this, 'addAdministrationMenu'));
        add_action('activate_plugin', array(&$this, 'generateSiteMap'));
        add_action('publish_post', array(&$this, 'generateSiteMap'));
        add_action('publish_page', array(&$this, 'generateSiteMap'));
        add_action('trashed_post', array(&$this, 'generateSiteMap'));
    }
    
    /**
     * Adds administration menu item
     * 
     */
    public function addAdministrationMenu() {
        $page = add_options_page(
            self::PLUGIN_NAME, 
            self::PLUGIN_NAME, 
            'administrator', 
            'google-sitemap', 
            array(&$this, 'renderSettingsPage')
        );
        add_action("admin_print_styles-{$page}", function() {
            wp_enqueue_style(
                'google-sitemap-settings', 
                trailingslashit(plugins_url(basename(dirname(__FILE__)))) . '/google-sitemap.css', 
                false, 
                '2011-04-28'
            );
        });
    }
    
    
    /**
     * Renders the settings page in the admini
     */
    public function renderSettingsPage() {

        $path = get_option( 'siteurl' ).'/sitemap.xml';
        ?>
        <h2>Google Sitemap XML</h2>
        
        <h3>Sitemap XML</h3>
        <p>Your sitemap is located here: <strong><a href="<?php echo $path ?>" target="_blank"><?php echo $path; ?></a></strong></p>
        <p>You can copy/paste it in <a href="https://www.google.com/webmasters/tools/" target="_blank">Google Webmaster Tools</a> which greatly increases the speed at which Google indexes your website.</p>

        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options'); ?>

            <div id="parameters">
                <h3>Parameters</h3>
                <p>
                    You can slightly tweak your XML sitemap as described in the <a href="http://sitemaps.org/protocol.php" target="_blank">Sitemaps XML Protocol</a>.<br /><br />
                    The following parameters will be applied to the global XML sitemap.  In other words, you cannot choose different parameters for each and every post/page, except the homepage.
                </p>    

                <div id="last-changed" class="section">
                    <h4>Last changed</h4>

                    <p class="form-label">Do you want to enable the <strong>last changed</strong> attribute?</p>
                    <p class="form-field"> 
                        <?php $lastChange = $this->getLastChangedOption(); ?>
                        <select name="google-sitemap-last-change" id="gsxml_hf" type="text" value="<?php echo $lastChange ?>" />
                            <option value="Disable" <?php if($lastChange=="Disable") {echo 'selected';}?>>disable</option>
                            <option value="Enable" <?php if($lastChange=="Enable") {echo 'selected';}?>>enable</option>
                        </select>
                    </p>
                    <div class="clearing"></div>
                </div>
                <div id="attributes" class="section">
                    <h4>Attributes</h4>
                    <p class="form-label">Do you want to enable the <strong>priority</strong> and the <strong>change frequency</strong> attributes ?  It is set to <strong>enabled</strong> by default.</p>
                    <p class="form-field"> 
                        <?php $priorityFreq = $this->getPriorityFrequency(); ?>
                        <select name="google-sitemap-priority-freq" id="gsxml_hf" type="text" value="<?php echo $priorityFreq ?>" />
                            <option value="Disable" <?php if($priorityFreq=="Disable") {echo 'selected';}?>>disable</option>
                            <option value="Enable" <?php if($priorityFreq=="Enable") {echo 'selected';}?>>enable</option>
                        </select>
                    </p>
                    <div class="clearing"></div>
                </div>
                <div id="homepage" class="section">
                    <h4>Homepage parameters</h4>
                    <h5 class="form-label">Priority</h5>
                    <p class="form-field">
                        <?php $homepagePriority = $this->getHomepagePriority(); ?>
                        <select name="google-sitemap-homepage-priority" id="gsxml_hp" type="text" value="<?php echo $homepagePriority; ?>" />
                            <?php 
                                for ($i=0; $i<1.05; $i+=0.1) {
                                    echo "<option value='".$i."' ";
                                     if ( $homepagePriority==$i ) {
                                          echo ' selected';
                                     } // end if

                                     echo ">";
                                     if($i==0) { echo "0.".$i;} 
                                     elseif($i==1.0) { echo $i.'0';} 
                                     else {echo $i;}
                                     echo "</option>";
                                } // end for
                            ?>
                        </select>
                    </p>
                    <h5 class="form-label">Frequency</h5>
                    <p class="form-field">
                        <?php $homepageFrequency = $this->getHomepageFrequency(); ?>
                        <select name="google-sitemap-homepage-frequency" id="gsxml_hf" type="text" value="<?php echo $homepageFrequency; ?>" />
                            <option value="always" <?php if($homepageFrequency=="always") {echo 'selected';}?>>always</option>
                            <option value="hourly" <?php if($homepageFrequency=="hourly") {echo 'selected';}?>>hourly</option>
                            <option value="weekly" <?php if($homepageFrequency=="weekly") {echo 'selected';}?>>weekly</option>
                            <option value="monthly" <?php if($homepageFrequency=="monthly") {echo 'selected';}?>>monhtly</option>
                            <option value="yearly" <?php if($homepageFrequency=="yearly") {echo 'selected';}?>>yearly</option>
                            <option value="never"  <?php if($homepageFrequency=="never") {echo 'selected';}?>>never</option>
                        </select>
                    </p>
                    <div class="clearing"></div>
                </div>
                <div id="general" class="section">
                    <h4>General parameters</h4>
                    <h5 class="form-label">Priority</h5>
                    <p class="form-field">
                        <?php $generalPriority = $this->getGeneralPagePriority(); ?>
                        <select name="google-sitemap-general-priority" id="gsxml_gp" type="text" value="<?php echo $generalPriority; ?>" />
                            <?php for ($i=0; $i<1.05; $i+=0.1) {
                                 echo "<option value='".$i."' ";
                                 if ($generalPriority==$i) {
                                      echo ' selected';
                                 } // end if

                                 echo ">";
                                 if($i==0) { echo "0.".$i;} 
                                 elseif($i==1.0) { echo $i.'0';} 
                                 else {echo $i;}
                                 echo "</option>";
                            } // end for
                            ?>
                        </select>
                   </p>

                   <h5 class="form-label">Frequency</h5>
                   <p class="form-field">
                       <?php $generalFrequency = $this->getGeneralPageFrequency(); ?>
                        <select name="google-sitemap-general-frequency" id="gsxml_gf" type="text" value="<?php echo $generalFrequency; ?>" />
                            <option value="always" <?php if($generalFrequency=='always') {echo 'selected';}?>>always</option>
                            <option value="hourly" <?php if($generalFrequency=='hourly') {echo 'selected';}?>>hourly</option>
                            <option value="weekly" <?php if($generalFrequency=='weekly') {echo 'selected';}?>>weekly</option>
                            <option value="monthly" <?php if($generalFrequency=='monthly') {echo 'selected';}?>>monthly</option>
                            <option value="yearly" <?php if($generalFrequency=='yearly') {echo 'selected';}?>>yearly</option>
                            <option value="never" <?php if($generalFrequency=='never') {echo 'selected';}?>>never</option>
                        </select>
                   </p>
                   <div class="clearing"></div>
                </div>
            </div>
            <div id="categories-tags" class="section">
                <h3>Categories and Tags</h3>
                <p>Include categories and tags in Google Sitemap</p>
                <h4 class="form-label">Categories:</h4>
                <p class="form-field">
                    <?php $includeCategories = $this->getIncludeCategories(); ?>
                    <select name="google-sitemap-categories" id="gsxml_cat" type="text" />
                        <option value="NotInclude" <?php if($includeCategories=="NotInclude") {echo 'selected';}?>>do not include</option>
                        <option value="Include" <?php if($includeCategories=="Include") {echo 'selected';}?>>include</option>
                    </select>
                </p>
                <h4 class="form-label tags">Tags:</h4>
                <p class="form-field">     
                    <?php $includeTags = $this->getIncludeTags(); ?>
                    <select name="google-sitemap-tags" id="gsxml_tag" type="text" />
                        <option value="NotInclude" <?php if($includeTags=="NotInclude") {echo 'selected';}?>>do not include</option>
                        <option value="Include" <?php if($includeTags=="Include") {echo 'selected';}?>>include</option>
                    </select>
                 </p>
                 <div class="clearing"></div>
            </div>
             <!-- Update the values -->
             <input type="hidden" name="action" value="update" />
             <input type="hidden" name="page_options" value="google-sitemap-homepage-priority,google-sitemap-general-priority,google-sitemap-homepage-frequency,google-sitemap-general-frequency,google-sitemap-priority-freq, google-sitemap-categories, google-sitemap-tags,google-sitemap-last-change" />
         <?php submit_button(); ?>
        </form>
        <?php
        $this->generateSiteMap();
    }
    
    /**
     * Generates site map XML
     */
    public function generateSiteMap() {
        
        $filename     = "sitemap.xml";
        $file_handler = fopen(ABSPATH.$filename, "w+");

        if (!$file_handler) {
            die;
        }
        $content = $this->getContent();
        fwrite($file_handler, $content);
        fclose($file_handler);
    }
    
    /**
     * Returns XML content from database
     * 
     * @global type $wpdb
     * @return string 
     */
    protected function getContent() {
        $xmlcontent  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xmlcontent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $xmlcontent .= $this->getRootPage();
        $xmlcontent .= $this->getPostPages();
        $xmlcontent .= $this->getCategoryPages();
        $xmlcontent .= $this->getTagPages();
        $xmlcontent .= '</urlset>'."\n";
        return $xmlcontent;
    }
    
    /**
     * Return the XML snippet for root page
     * 
     * @return string
     */
    protected function getRootPage() {
        return sprintf("
            <url>
              <loc>%s</loc>%s%s
            </url>",
            get_option('siteurl'), 
            $this->isLastChangedEnabled() ? "<lastmod>".date('Y-m-d')."</lastmod>" : '',
            $this->isPriorityFrequencyEnabled() ? 
                "<changefreq>".$this->getHomepageFrequency()."</changefreq><priority>".$this->getHomepagePriority()."</priority>" :
                ''
        );
    }
    
    protected function getPostPages() {
        global $wpdb;
        $table_name = $wpdb->prefix . "posts";
        $query      = "SELECT year(post_modified) AS y, month(post_modified) AS m, day(post_modified) AS d, ID,post_title, post_modified,post_name, post_type, post_parent FROM $table_name WHERE post_status = 'publish' AND (post_type = 'page' OR post_type = 'post') ORDER BY post_date DESC";
        $myrows     = $wpdb->get_results($query); 
        $xmlcontent = '';

        foreach ($myrows as $myrow) {

            $permalink = utf8_encode($myrow->post_name);
            $type = $myrow->post_type;
            $date = $myrow->y."-";
            $date.= $myrow->m < 10 ? "0".$myrow->m."-" : $myrow->m."-";
            $date.= $myrow->d < 10 ? "0".$myrow->d : $myrow->d;
            $id   = $myrow->ID;
            $url  = get_permalink($id);

            $xmlcontent .= sprintf("
                <url>
                  <loc>%s</loc>%s%s
                </url>",
                $url, 
                $this->isLastChangedEnabled() ? "<lastmod>".$date."</lastmod>" : '',
                $this->isPriorityFrequencyEnabled() ? 
                    "<changefreq>".$this->getGeneralPageFrequency()."</changefreq><priority>".$this->getGeneralPagePriority()."</priority>" :
                    ''
            );
        }
        return $xmlcontent;
    }
    
    protected function getCategoryPages() {
        
        if(!$this->shouldIncludeCategories()) {
            return '';
        }
        global $wpdb;
        
        $xmlcontent     = '';
        $table_terms    = $wpdb->prefix . "terms";
        $table_taxonomy = $wpdb->prefix . "term_taxonomy";
        $query          = "SELECT $table_terms.term_id, $table_taxonomy.taxonomy FROM $table_terms, $table_taxonomy WHERE ($table_terms.term_id = $table_taxonomy.term_id AND $table_taxonomy.taxonomy = 'category') ";
        $mycats         = $wpdb->get_results($query); 
        $date           = date('Y-m-d');

        foreach ($mycats as $mycat) {
            $xmlcontent .= sprintf("
                <url>
                  <loc>%s</loc>%s%s
                </url>",
                get_category_link( $mycat->term_id ), 
                $this->isLastChangedEnabled() ? "<lastmod>".$date."</lastmod>" : '',
                $this->isPriorityFrequencyEnabled() ? 
                    "<changefreq>".$this->getGeneralPageFrequency()."</changefreq><priority>".$this->getGeneralPagePriority()."</priority>" :
                    ''
            );
        }
        return $xmlcontent;
    }
    
    protected function getTagPages() {
        
        if(!$this->shouldIncludeTags()) {
            return '';
        }
        global $wpdb;
        
        $xmlcontent     = '';
        $table_terms    = $wpdb->prefix . "terms";
        $table_taxonomy = $wpdb->prefix . "term_taxonomy";
        $query          = "SELECT $table_terms.term_id, $table_taxonomy.taxonomy FROM $table_terms, $table_taxonomy WHERE ($table_terms.term_id = $table_taxonomy.term_id AND $table_taxonomy.taxonomy = 'post_tag') ";
        $mytags         = $wpdb->get_results($query); 

        //  Output each category link with the date being when it 
        $date = date('Y-m-d');

        foreach ($mytags as $mytag) {
            $xmlcontent .= sprintf("
                <url>
                  <loc>%s</loc>%s%s
                </url>",
                get_tag_link( $mytag->term_id ), 
                $this->isLastChangedEnabled() ? "<lastmod>".$date."</lastmod>" : '',
                $this->isPriorityFrequencyEnabled() ? 
                    "<changefreq>".$this->getGeneralPageFrequency()."</changefreq><priority>".$this->getGeneralPagePriority()."</priority>" :
                    ''
            );
        }
        return $xmlcontent;
    }
    
    /**
     * Returns the last changed option
     * 
     * @return string
     */
    protected function getLastChangedOption() {
        return get_option('google-sitemap-last-change', self::ENABLED);
    }
    
    /**
     * Determine whether the change last changed parameter is set
     * 
     * @return bool
     */
    protected function isLastChangedEnabled() {
        return $this->getLastChangedOption() == self::ENABLED;
    }
    
    /**
     * Returns the priority frequency
     * 
     * @return string 
     */
    protected function getPriorityFrequency() {
        return get_option('google-sitemap-priority-freq', self::ENABLED);
    }
    
    /**
     * Determines whether the priority frequency setting is enabled
     * 
     * @return bool
     */
    protected function isPriorityFrequencyEnabled() {
        return $this->getPriorityFrequency() == self::ENABLED;
    }
    
    /**
     * Returns the homepage priority
     * 
     * @return string
     */
    protected function getHomepagePriority() {
        return get_option('google-sitemap-homepage-priority', '0.5');
    }
    
    /**
     * Returns the homepage frequency
     * 
     * @return string
     */
    protected function getHomepageFrequency() {
        return get_option('google-sitemap-homepage-frequency', 'weekly');
    }
    
    /**
     * Returns the general page priority
     * 
     * @return string
     */
    protected function getGeneralPagePriority() {
        return get_option('google-sitemap-general-priority', '0.5');
    }
    
    /**
     * Returns the general page frequency
     * 
     * @return string
     */
    protected function getGeneralPageFrequency() {
        return get_option('google-sitemap-general-frequency', 'weekly');
    }
    
    /**
     * Returns whether to include categories in sitemap
     * 
     * @return string
     */
    protected function getIncludeCategories() {
        return get_option('google-sitemap-categories', self::INCLUDED);
    }
    
    /**
     * Determine whether categories shoud be included
     * 
     * @return bool
     */
    protected function shouldIncludeCategories() {
        return $this->getIncludeCategories() == self::INCLUDED;
    }
    
    /**
     * Returns whether to include tags in sitemap
     * @return string
     */
    protected function getIncludeTags() {
        return get_option('google-sitemap-tags', self::INCLUDED);
    }
    
    /**
     * Determine whether tags should be included in the site map
     * 
     * @return bool
     */
    protected function shouldIncludeTags() {
        return $this->getIncludeTags() == self::INCLUDED;
    }
}

$oGoogleSiteMap = new GoogleSiteMap();
$oGoogleSiteMap->initialise();