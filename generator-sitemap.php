<?php 
    set_time_limit(0);
    include("include/sitemap.php");
    $sitemap = new sitemap();
 
    //игнорировать ссылки с расширениями:
    $sitemap->set_ignore(array("javascript:", ".css", ".js", ".ico", ".jpg", ".png", ".jpeg", ".swf", ".gif", "liveinternet.ru", "#dialog"));
 
    //ссылка Вашего сайта:
    $sitemap->get_links("http://mysite.ru");
 
    $map = $sitemap->generate_sitemap();
    $fp = fopen('sitemap.xml', 'a');
    ftruncate($fp, 0); // очищаем файл
    fwrite($fp, $map);
?>
