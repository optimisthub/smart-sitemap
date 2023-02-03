<?php
 
use samdark\sitemap\Sitemap;
use samdark\sitemap\Index;
 
class SmartSitemap 
{
    public $sitemapPath  = null;
    public $siteUrl      = null;
    public $postTypes    = ['post', 'page', 'product'];
    public $size         = 1000;
    public $expiration   = null;

    public function __construct()
    {  
        $this->sitemapPath  = ABSPATH.'/sitemaps/';
        $this->siteUrl      = 'https://' . $_SERVER['HTTP_HOST'] .'/';
        $this->expiration   = strtotime("-1 day");
        
        add_action( 'init', [$this, 'init']);
    }

    public function init()
    {   
        self::createDir($this->sitemapPath); 
        self::cleanDirectory();

        foreach($this->postTypes as $type)
        {
            self::generateSitemap($type);
        }

        self::generateSitemapIndex();
    }

    private function generateSitemap($type)
    { 
        $filename = $this->sitemapPath .'/' .$type.'-sitemap.xml';
        $sitemap = new Sitemap($filename);

        $sitemap->setMaxUrls($this->size);
        $posts = [];
        $query = self::getPosts($type);
        $posts[$type] = data_get($query, 'posts');

        if($posts[$type])
        {
            foreach ($posts[$type] as $key => $value) 
            {
                $sitemap->addItem(get_permalink(data_get($value, 'ID')), strtotime(data_get($value,'post_date',time())));
            }
        }  

        if(!file_exists($filename))
        {
            $sitemap->write();
        }

        if (filectime($filename) > $this->expiration) {
            $sitemap->write();
        }

    }

    private function cleanDirectory()
    {
        $files = glob($this->sitemapPath.'*');
        foreach($files as $file){  
            if(is_file($file)) {
                @unlink($file);  
            }
        }
    }

    private function createDir()
    {
        @mkdir($this->sitemapPath);
    }

    private function getPosts($postType)
    {
        return new WP_Query( 
            [
                'post_type'         => [$postType], 
                'post_status'       => 'publish',
                'posts_per_page'    => -1,
            ]    
        );
    }

    private function generateSitemapIndex()
    {
        $urls = [];

        $files = glob($this->sitemapPath.'*.xml');
        foreach($files as $file){  
            if(is_file($file)) {
                $urls[] = $file;
            }
        }

        $filename = $this->sitemapPath.'/sitemap-index.xml';

        $sitemap = new Index($filename);
        
        foreach($urls as $urzl)
        {  
            $url = self::formatUrl($urzl);
            $sitemap->addSitemap($this->siteUrl.''.$url, time(), Sitemap::DAILY, 0.3);
        } 
        
        $sitemap->write();  
    }

    private function formatUrl($url)
    {
        $url = explode('/', $url);
        $url = array_slice($url,-2,2,true);
        $url = implode('/',$url); 
        return $url;
    }
} 

new SmartSitemap();
