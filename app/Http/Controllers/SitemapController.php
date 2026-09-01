<?php

namespace App\Http\Controllers;

use App\Http\Services\SiteMapService;

class SitemapController extends Controller
{
    protected $siteMapService;

    public function __construct(SiteMapService $siteMapService)
    {
        $this->siteMapService = $siteMapService;
    }

    public function generateSitemap()
    {
        return $this->siteMapService->generateSitemap();
    }

    public function redirectToView()
    {
        return $this->siteMapService->redirectToView();
    }

    public function blogMap()
    {
        return $this->siteMapService->blogMap();
    }

    public function itemMap()
    {
        return $this->siteMapService->itemMap();
    }

    public function categoryMap()
    {
        return $this->siteMapService->categoryMap();
    }

    public function subcatMap()
    {
        return $this->siteMapService->subcatMap();
    }

    public function vendorMap()
    {
        return $this->siteMapService->vendorMap();
    }
}
