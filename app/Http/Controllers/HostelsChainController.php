<?php

namespace App\Http\Controllers;

use App\Models\HostelsChain;
use App\Services\HostelChainsService;
use App\Services\Listings\CityListingsService;
use App\Services\Listings\ListingsPoiService;
use Illuminate\Support\Str;
use Lib\FormHandler;

class HostelsChainController extends Controller
{
    public function __construct(private ListingsPoiService $listingsPoiService, private CityListingsService $cityListingsService, private HostelChainsService $hostelChainsService)
    {
    }

    public function index()
    {
        $chains = HostelsChain::isActive()->get();
        $ogThumbnail = url('images', 'best-hostel-chains.jpg');

        return view('hostelsChain.index', compact('chains', 'ogThumbnail'));
    }

    public function show(HostelsChain $hostelChain)
    {
        $listings = $hostelChain->listings()->areLive()->orderBy('name', 'asc')->get();

        $picUrls = $this->cityListingsService->getListingsPics($listings);

        $callback = function ($listing) {
            return [
                $listing->latitude,
                $listing->longitude,
                $listing->id,
                $listing->name,
                $listing->city,
                $listing->country,
            ];
        };

        $pois = $this->listingsPoiService->getMapPoi($listings, $callback);

        $ogThumbnail = $hostelChain->pic !== null ? url($hostelChain->pic->url('medium')) : '';

        $FAQs = $this->hostelChainsService->getFAQs($hostelChain);

        return view('hostelsChain.show', compact('hostelChain', 'listings', 'ogThumbnail', 'pois', 'picUrls', 'FAQs'));
    }

    public function imageCreate(HostelsChain $hostelChain)
    {
        $existing = $hostelChain->pic;
        $existingAsCollection = collect($existing ? [$existing] : []);

        // FileList

        $fileList = new \Lib\FileListHandler($existingAsCollection, null, null, true);
        $fileList->picListSizeTypeNames = ['thumbnails'];

        $response = $fileList->go();
        if ($response !== null) {
            return $response;
        }

        // FileUpload

        $fileUpload = new \Lib\FileUploadHandler(['jpg', 'jpeg', 'gif', 'png'], 15, 1, $existing ? 1 : 0);
        $fileUpload->minImageWidth = HostelsChain::IMAGE_WIDTH;
        $fileUpload->minImageHeight = HostelsChain::IMAGE_HEIGHT;

        $response = $fileUpload->handleUpload([$hostelChain, 'savePic']);

        if ($response !== null) {
            return $response;
        }

        return view('hostelsChain.staff.imageCreate', compact('hostelChain', 'fileList', 'fileUpload'));
    }

    public function dashboard($pathParameters = null)
    {
        $fields = [
            'id' => ['isPrimaryKey' => true, 'editType' => 'ignore', 'searchAndListType' => 'ignore'],
            'name' => ['maxLength' => 80, 'validation' => 'required'],
            'meta_title' => [],
            'meta_description' => ['type' => 'textarea', 'rows' => 3],
            'affiliate_links' => ['type' => 'url', 'maxLength' => 250],
            'website_link' => [],
            'instagram_link' => [],
            'slug' => ['maxLength' => 80,
                'setValue' => function ($formHandler, $model, $value): void {
                    $slug = ! empty($value) ? $value : $formHandler->inputData['name'];

                    $model->slug = Str::slug($slug, '-');
                }, ],
            'description' => ['type' => 'WYSIWYG', 'rows' => 20],
            'isActive' => ['type' => 'checkbox', 'value' => 1, 'checkboxText' => ' '],
            'videoURL' => [],
            'videoEmbedHTML' => ['type' => 'display'],
            'videoSchema' => ['type' => 'display'],
        ];

        $formHandler = new FormHandler('HostelsChain', $fields, $pathParameters, 'App\Models');

        $formHandler->allowedModes = $this->getAllowedModes();
        $formHandler->showFieldnameIfMissingLang = true;
        $formHandler->listPaginateItems = 20;
        $formHandler->listSelectFields = ['name', 'slug', 'description', 'isActive'];

        return $formHandler->go('hostelsChain.staff.index', $this->getAllowedModes(), 'searchAndList');
    }

    private function getAllowedModes()
    {
        return auth()->user()->hasPermission('admin') ?
            ['searchForm', 'searchAndList', 'updateForm', 'update', 'delete', 'multiDelete', 'insert', 'insertForm'] :
            ['searchForm', 'searchAndList', 'display'];
    }
}
