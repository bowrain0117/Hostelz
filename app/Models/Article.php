<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Lib\BaseModel;
use Lib\Emailer;
use Lib\PageCache;

class Article extends BaseModel
{
    protected $table = 'articles';

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    public static $statusOptions = [
        'proposal', 'returnedProposal', 'deniedProposal', 'acceptedProposal', 'inProgress',
        'submitted', 'denied', 'returned', 'accepted', 'published', 'removed',
    ];

    public static $placementOptions = ['articles', 'unlisted article', 'hostel owners tips', 'exclusive content' /* 'blog', 'external' */];

    public static $payStatusOptions = ['notForPay', 'paid'];

    public const THUMBNAIL_WIDTH = 120; // these values should match the sizes in articles.scss

    public const THUMBNAIL_HEIGHT = 120;

    public function save(array $options = []): void
    {
        parent::save($options);

        $this->clearRelatedPageCaches();
    }

    public function delete(): void
    {
        foreach ($this->pics as $pic) {
            $pic->delete();
        }
        parent::delete();
        $this->clearRelatedPageCaches();
    }

    public function finalArticleWithoutTags()
    {
        return getPlainTextFromHTMLPage(preg_replace('/\[pic:(.+)\]/', '', $this->finalArticle));
    }

    public function getSnippet($length)
    {
        return wholeWordTruncate($this->finalArticleWithoutTags(), $length);
    }

    public function getArticleTextForDisplay()
    {
        $articleText = ($this->finalArticle != '' ? $this->finalArticle : $this->originalArticle);

        if (strpos($articleText, '<head>') !== false) {
            preg_match('|<head>(.*)</head>|s', $articleText, $matches);
            $articleHeaderInsert = $matches[1];
            $articleText = str_replace($matches[0], '', $articleText);
        } else {
            $articleHeaderInsert = '';
        }

        if (strpos($articleText, '<p>') === false && strpos($articleText, '<br>') === false) {
            $articleText = nl2br($articleText);
        }

        if (! $this->pics->isEmpty()) {
            if (preg_match_all('/(\[pic:.+\])/', $articleText, $matches)) {
                foreach ($matches[1] as $match) {
                    preg_match('/\[pic:(.+)\]/', $match, $captionMatch);
                    $picCaption = trim($captionMatch[1]);
                    if ($picCaption == '') {
                        logWarning('Invalid pic.');

                        continue;
                    }
                    $pic = $this->pics->where('caption', $picCaption)->first();
                    if (! $pic) {
                        logWarning("Unknown pic '$picCaption'.");

                        continue;
                    }
                    $articleText = str_replace($match, '<img class="articlePic photoFrame" src="' . $pic->url(['originals']) . "\" 
                	    alt='{$pic->caption}' title='{$pic->caption}'>", $articleText);
                }
            }
        }

        $articleText = replaceShortcodes($articleText);

        return [
            'text' => $articleText, 'headerInsert' => $articleHeaderInsert,
        ];
    }

    public function getArticleTitle()
    {
        return replaceShortcodes($this->title);
    }

    public function getMetaTitleForDisplay()
    {
        return replaceShortcodes($this->metaTitle);
    }

    public function getMetaDescriptionForDisplay()
    {
        return replaceShortcodes($this->metaDescription);
    }

    public function privatePreviewPassword()
    {
        return abs(crc32('YYa$ai' . $this->id . '#fQva*'));
    }

    public function isForLogedInContent()
    {
        return $this->placementType === 'exclusive content';
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    /* Static */

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $return = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'userID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, 'validation' => 'required', ],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'submitDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
                    'language' => ['type' => 'select', 'options' => Languages::allCodesKeyedByName(), 'optionsDisplay' => 'keys'],
                    'title' => ['maxLength' => 255, 'validation' => 'required'],
                    'metaTitle' => ['maxLength' => 255],
                    'metaDescription' => ['type' => 'textarea', 'rows' => 3],
                    'authorName' => ['maxLength' => 255],
                    'proposal' => ['type' => 'textarea', 'rows' => 5],
                    'publishDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
                    'updateDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
                    'placementType' => ['type' => 'select', 'options' => self::$placementOptions],
                    'placement' => ['maxLength' => 255],
                    'originalArticle' => ['type' => 'WYSIWYG', 'rows' => 5, 'sanitize' => 'WYSIWYG'],
                    'finalArticle' => ['type' => 'WYSIWYG', 'rows' => 25, 'sanitize' => 'WYSIWYG'],
                    'comments' => ['type' => 'display', 'searchType' => 'text', 'unescaped' => true],
                    'newComment' => ['type' => 'ignore', 'editType' => 'textarea', 'rows' => 2,
                        'setValue' => function ($formHandler, $model, $value): void {
                            if ($value != '') {
                                $model->addComment($value, 'staff');
                            }
                        },
                    ],
                    'newUserComment' => ['type' => 'select', 'editType' => 'ignore', 'options' => ['0', '1'], 'optionsDisplay' => 'translate'], /* put in controller -> // clear flag now they we've read the new comment if ($qf->mode == 'display') dbQuery("UPDATE articles SET newUserComment=0 WHERE id=".dbEscapeInt($qf->where['id'])); */
                    'notes' => ['type' => 'textarea', 'rows' => 3],
                    'payStatus' => ['type' => 'select', 'options' => self::$payStatusOptions, 'optionsDisplay' => 'translate'],
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $return;
    }

    /* Accessors & Mutators */

    /* Static */

    public static function isShowCategoryForNotLogedIn($categorySlug)
    {
        return $categorySlug !== self::categoriesData()['exclusive content']->slug;
    }

    public static function getCategoryBySlug($categorySlug)
    {
        return collect(self::categoriesData())->filter(function ($value, $key) use ($categorySlug) {
            return $value->slug === $categorySlug;
        })->first();
    }

    /* Misc */

    public function addPic($picFilePath, $caption = null)
    {
        return Pic::makeFromFilePath($picFilePath, [
            'subjectType' => 'articles', 'subjectID' => $this->id, 'type' => '', 'status' => 'ok',
            'caption' => (string) $caption,
        ], [
            'originals' => [],
            // We just save the thumbnail size of all photos, even though only the primary photo's thumbnail is actually used.
            'thumbnails' => ['saveAsFormat' => 'jpg', 'outputQuality' => 80,
                'absoluteWidth' => self::THUMBNAIL_WIDTH, 'absoluteHeight' => self::THUMBNAIL_HEIGHT, ],
        ]);
    }

    public function addComment($comment, $asStaffOrUser = 'staff')
    {
        if ($comment == '') {
            throw new Exception('Empty comment.');
        }

        if ($asStaffOrUser == 'staff') { // If we're writing a comment, notify the user...
            if (! $this->user) {
                logError('No user with id ' . $this->userID . '.');

                return false;
            }
            Emailer::send(
                $this->user,
                'New Hostelz.com Staff Article Comment',
                'generic-email',
                ['text' => "A Hostelz.com staff person has added a new comment to one of your articles.\n\nTo view the comment, see your Travel Articles list.",
                    // TODO: After the new site is live, add the link to their articles page here.
                ], Config::get('custom.adminEmail'));
            $this->newStaffComment = true;
        } elseif ($asStaffOrUser == 'user') {
            $this->newUserComment = true;
        } else {
            throw new Exception('Unknown asStaffOrUser value.');
        }

        $this->comments = $this->comments . '(' . date('M j, Y') . ') <b>' . ($asStaffOrUser == 'staff' ? 'Hostelz.com:' : 'User:') . '</b> ' . htmlspecialchars($comment) . "<br>\n";

        return true;
    }

    public function clearRelatedPageCaches(): void
    {
        PageCache::clearByTag('articles');
    }

    public function thumbnailUrl($type = 'thumbnails')
    {
        $primaryPhoto = $this->pics->where('isPrimary', true)->first();
        if (! $primaryPhoto) {
            $primaryPhoto = $this->pics->first();
        } // just use the first pic
        if (! $primaryPhoto) {
            return routeURL('images', 'noImage.png');
        }

        return $primaryPhoto->url([$type]);
    }

    public function url($urlType = 'auto', $language = null)
    {
        if ($this->placement == '') {
            throw new Exception('No placement (slug).');
        }

        switch ($this->placementType) {
            case 'articles':
            case 'unlisted article':
            case 'hostel owners tips':
            case 'exclusive content':
                return routeURL('articles', $this->placement, $urlType, $language);

            default:
                throw new Exception("Don't know how to create a URL for this placement type.");
        }
    }

    public function getURL($urlType = 'auto', $language = null)
    {
        return $this->url($urlType, $language);
    }

    public function getCategoryAttribute()
    {
        $categoriesData = self::categoriesData();

        return (isset($categoriesData[$this->placementType]))
            ? (object) $categoriesData[$this->placementType]
            : null;
    }

    public static function getBlogs()
    {
        return self::query()
            ->published()
            ->where('placementType', 'articles')
            ->orderBy('publishDate', 'DESC')
            ->with('pics')
            ->get();
    }

    public static function categoriesData(): array
    {
        return [
            'articles' => (object) [
                'placementType' => 'articles',
                'showOnIndex' => true,
                'slug' => 'backpacker-hostel-tips',
                'url' => routeURL('articles', 'backpacker-hostel-tips'),

                'breadcrumb' => langGet('articles.categories.hostelTips.breadcrumb'),
                'title' => langGet('articles.categories.hostelTips.title'),
                'metaTitle' => langGet('articles.categories.hostelTips.metaTitle'),
                'metaDescription' => langGet('articles.categories.hostelTips.metaDescription'),
                'titleIndex' => langGet('articles.categories.hostelTips.titleIndex'),
                'text' => langGet('articles.categories.hostelTips.text'),
            ],
            'hostel owners tips' => (object) [
                'placementType' => 'hostel owners tips',
                'showOnIndex' => true,
                'slug' => 'hostel-owners-tips-management',
                'url' => routeURL('articles', 'hostel-owners-tips-management'),

                'breadcrumb' => langGet('articles.categories.ownersTips.breadcrumb'),
                'title' => langGet('articles.categories.ownersTips.title'),
                'metaTitle' => langGet('articles.categories.ownersTips.metaTitle'),
                'metaDescription' => langGet('articles.categories.ownersTips.metaDescription'),
                'titleIndex' => langGet('articles.categories.ownersTips.titleIndex'),
                'text' => langGet('articles.categories.ownersTips.text'),
            ],
            'exclusive content' => (object) [
                'placementType' => 'exclusive content',
                'showOnIndex' => true,
                'slug' => 'best-hostel-tips-backpacking',
                'url' => routeURL('articles', 'best-hostel-tips-backpacking'),

                'breadcrumb' => langGet('articles.categories.exclusiveContent.breadcrumb'),
                'title' => langGet('articles.categories.exclusiveContent.title'),
                'metaTitle' => langGet('articles.categories.exclusiveContent.metaTitle'),
                'metaDescription' => langGet('articles.categories.exclusiveContent.metaDescription'),
                'titleIndex' => langGet('articles.categories.exclusiveContent.titleIndex'),
                'text' => langGet('articles.categories.exclusiveContent.text'),
            ],
        ];
    }

    /* Relationships */

    public function pics()
    {
        return $this->hasMany(\App\Models\Pic::class, 'subjectID')->where('subjectType', 'articles')->orderBy('picNum');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'userID');
    }
}
