<?
class sitemap
{
    private $sitemap_urls = array();
    private $base;
    private $protocol;
    private $domain;
    private $check = array();
    private $proxy = "";

    public function set_ignore($ignore_list){
        $this->check = $ignore_list;
    }
    
    public function set_proxy($host_port){
        $this->proxy = $host_port;
    }
   
    private function validate($url){
        $valid = true;
        //add substrings of url that you don't want to appear using set_ignore() method
        foreach($this->check as $val)
        {
            if(stripos($url, $val) !== false)
            {
                $valid = false;
                break;
            }
        }
        return $valid;
    }
 
    
    private function multi_curl($urls){
        // for curl handlers
        $curl_handlers = array();
        //setting curl handlers
        foreach ($urls as $url)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            if (isset($this->proxy) && !$this->proxy == '')
            {
                curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
            }
            $curl_handlers[] = $curl;
        }
        
        $multi_curl_handler = curl_multi_init();
 
        // adding all the single handler to a multi handler
        foreach($curl_handlers as $key => $curl)
        {
            curl_multi_add_handle($multi_curl_handler,$curl);
        }
 
        
        do
        {
            $multi_curl = curl_multi_exec($multi_curl_handler, $active);
        }
        while ($multi_curl == CURLM_CALL_MULTI_PERFORM  || $active);
 
        foreach($curl_handlers as $curl)
        {
            //checking for errors
            if(curl_errno($curl) == CURLE_OK)
            {
                //if no error then getting content
                $content = curl_multi_getcontent($curl);
                //parsing content
                $this->parse_content($content);
            }
        }
        curl_multi_close($multi_curl_handler);
        return true;
    }
 
    
    public function get_links($domain){
        //getting base of domain url address
        $this->base = str_replace("http://", "", $domain);
        $this->base = str_replace("https://", "", $this->base);
        $host = explode("/", $this->base);
        $this->base = $host[0];
        //getting proper domain name and protocol
        $this->domain = trim($domain);
        if(strpos($this->domain, "http") !== 0)
        {
            $this->protocol = "http://";
            $this->domain = $this->protocol.$this->domain;
        }
        else
        {
            $protocol = explode("//", $domain);
            $this->protocol = $protocol[0]."//";
        }
 
        if(!in_array($this->domain, $this->sitemap_urls))
        {
            $this->sitemap_urls[] = $this->domain;
        }
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->domain);
        if (isset($this->proxy) && !$this->proxy == '')
        {
            curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $page = curl_exec($curl);
        curl_close($curl);
        $this->parse_content($page);
    }
 
    
    private function parse_content($page){
        //getting all links from href attributes
        preg_match_all("/<a[^>]*href\s*=\s*'([^']*)'|".
                    '<a[^>]*href\s*=\s*"([^"]*)"'."/is", $page, $match);
        //storing new links
        $new_links = array();
        for($i = 1; $i < sizeof($match); $i++)
        {
            
            foreach($match[$i] as $url)
            {
                
                if(strpos($url, "http") === false  && trim($url) !== "")
                {
                    
                    if($url[0] == "/") $url = substr($url, 1);
                   
                    else if($url[0] == ".")
                    {
                        while($url[0] != "/")
                        {
                            $url = substr($url, 1);
                        }
                        $url = substr($url, 1);
                    }
                    
                    $url = $this->protocol.$this->base."/".$url;
                }
                
                if(!in_array($url, $this->sitemap_urls) && trim($url) !== "")
                {
                    
                    if($this->validate($url))
                    {
                        
                        if(strpos($url, "http://".$this->base) === 0 || strpos($url, "https://".$this->base) === 0)
                        {
                            //adding url to sitemap array
                            $this->sitemap_urls[] = $url;
                            //adding url to new link array
                            $new_links[] = $url;
                        }
                    }
                }
            }
        }
        $this->multi_curl($new_links);
        return true;
    }
 
    
    public function get_array(){
        return $this->sitemap_urls;
    }
 
    
    public function ping($sitemap_url, $title ="", $siteurl = ""){
        // for curl handlers
        $curl_handlers = array();
 
        $sitemap_url = trim($sitemap_url);
        if(strpos($sitemap_url, "http") !== 0)
        {
            $sitemap_url = "http://".$sitemap_url;
        }
        $site = explode("//", $sitemap_url);
        $start = $site[0];
        $site = explode("/", $site[1]);
        $middle = $site[0];
        if(trim($title) == "")
        {
            $title = $middle;
        }
        if(trim($siteurl) == "")
        {
            $siteurl = $start."//".$middle;
        }
        
        $urls[0] = "http://www.google.com/webmasters/tools/ping?sitemap=".urlencode($sitemap_url);
        $urls[1] = "http://www.bing.com/webmaster/ping.aspx?siteMap=".urlencode($sitemap_url);
        $urls[2] = "http://search.yahooapis.com/SiteExplorerService/V1/updateNotification".
                "?appid=YahooDemo&url=".urlencode($sitemap_url);
        $urls[3] = "http://submissions.ask.com/ping?sitemap=".urlencode($sitemap_url);
        $urls[4] = "http://rpc.weblogs.com/pingSiteForm?name=".urlencode($title).
                "&url=".urlencode($siteurl)."&changesURL=".urlencode($sitemap_url);
 
        //setting curl handlers
        foreach ($urls as $url)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURL_HTTP_VERSION_1_1, 1);
            $curl_handlers[] = $curl;
        }
        //initiating multi handler
        $multi_curl_handler = curl_multi_init();
 
        // adding all the single handler to a multi handler
        foreach($curl_handlers as $key => $curl)
        {
            curl_multi_add_handle($multi_curl_handler,$curl);
        }
 
        
        do
        {
            $multi_curl = curl_multi_exec($multi_curl_handler, $active);
        }
        while ($multi_curl == CURLM_CALL_MULTI_PERFORM  || $active);
 
        // check if there any error
        $submitted = true;
        foreach($curl_handlers as $key => $curl)
        {
            //you may use curl_multi_getcontent($curl); for getting content
            //and curl_error($curl); for getting errors
            if(curl_errno($curl) != CURLE_OK)
            {
                $submitted = false;
            }
        }
        curl_multi_close($multi_curl_handler);
        return $submitted;
    }
 
    //generates sitemap
    public function generate_sitemap(){
        $sitemap = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        foreach($this->sitemap_urls as $url)
        {
            $url_tag = $sitemap->addChild("url");
            $url_tag->addChild("loc", htmlspecialchars($url));
        }
        return $sitemap->asXML();
    }
}
?>
