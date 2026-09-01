<?php

namespace App\Console\Commands;

use App\Http\Services\SiteMapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically Generate an XML Sitemap';

    /**
     * Execute the console command.
     *
     * @return int
     */
    // return Command::SUCCESS;
    protected $siteMapService;

    public function handle()
    {
        $siteMapService = new SiteMapService;
        $siteMapService->itemMap();
        $siteMapService->categoryMap();
        $siteMapService->subcatMap();
        $siteMapService->vendorMap();
        $siteMapService->generateSitemap();
        $siteMapService->redirectToView();
        $siteMapService->blogMap();
        Log::info('Sitemap generation completed successfully.');

        $generatedSitemapFiles = [
            [
                'filename' => 'allsitemap.xml',
                'filepath' => asset('allsitemap.xml')
            ],
            [
                'filename' => 'sitemap.html',
                'filepath' => asset('sitemap.html')
            ],
            [
                'filename' => 'blog-sitemap.xml',
                'filepath' => asset('sitemaps/blog-sitemap.xml')
            ],
            [
                'filename' => 'category-sitemap.xml',
                'filepath' => asset('sitemaps/category-sitemap.xml')
            ],
            [
                'filename' => 'item-sitemap.xml',
                'filepath' => asset('sitemaps/item-sitemap.xml')
            ],
            [
                'filename' => 'subcategory-sitemap.xml',
                'filepath' => asset('sitemaps/subcategory-sitemap.xml')
            ],
            [
                'filename' => 'vendor-sitemap.xml',
                'filepath' => asset('sitemaps/vendor-sitemap.xml')
            ],
        ];

        $this->info(json_encode($generatedSitemapFiles));

        return Command::SUCCESS;
    }
}
