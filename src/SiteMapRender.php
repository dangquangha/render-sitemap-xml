<?php

namespace SitemapRenderXML;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

class SiteMapRender
{
    /**
     * Danh sách url cần render
     * @var array
     */
    protected $urls = [];

    public function setUrls($urls = [])
    {
        $this->urls = $urls;
        return $this;
    }

    public function render()
    {
        if ($this->urls != [])
            foreach ($this->urls as $urlsSiteMap) {
            $fileName = $urlsSiteMap['sitemap']->file_name;
            $sitemap = App::make('sitemap');

            foreach ($urlsSiteMap['data'] as $urlId => $url) {
                $arrIdsToUpdate[] = $urlId;
                $sitemap->add(URL::to('/' . $url), Carbon::now(), 0.7, 'daily');
            }

            if ( File::exists(public_path('sitemap/' . $fileName . '.xml')) ) {
                $content = $sitemap->render('xml')->getContent();
                $content = $this->reBuildContent($content, 'sitemap/' . $fileName . '.xml');
                file_put_contents(public_path('sitemap/' . $fileName . '.xml'), $content);
            } else {
                $sitemap->store('xml', 'sitemap/' . $fileName);
            }

            $this->updateStatusSiteMapOfUrl($urlsSiteMap['sitemap'], $arrIdsToUpdate ?? []);
        }
    }

    public function reBuildContent($content, $path)
    {
        $content = explode("\n", $content);
        array_splice($content, 0, 3);
        $content = implode("\n", $content);

        $oldContent = file_get_contents(public_path($path));
        // option 1
        $oldContent = explode("\n", $oldContent);
        array_splice($oldContent, count($oldContent) - 3, 3);
        $oldContent = implode("\n", $oldContent);
        // option 2
//        $oldContent = str_replace('</urlset>', '', $oldContent);

        return $oldContent . $content;
    }

    public function updateStatusSiteMapOfUrl($siteMap, $arrIdsUpdate)
    {
        DB::table($siteMap->table_name)
            ->whereIn('id', $arrIdsUpdate)
            ->update(['check_sitemap' => 1]);
    }
}