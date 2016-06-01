<?php

namespace App\Http\Controllers\Admin;

use Acme;
use App\Http\Controllers\Admin\AdminFoundation\CustomFields;
use App\Models;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostContent;
use App\Models\PostMeta;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class PostController extends BaseAdminController
{

    use CustomFields;

    public $bodyClass = 'post-controller', $routeLink = 'posts';

    public function __construct()
    {
        parent::__construct();

        $this->_setPageTitle('Posts', 'manage posts');
        $this->_setBodyClass($this->bodyClass);

        $this->_loadAdminMenu($this->routeLink);
    }

    public function getIndex(Request $request, Post $object)
    {
        $this->_setBodyClass($this->bodyClass . ' posts-list-page');
        return $this->_viewAdmin('posts.index');
    }

    public function postIndex(Request $request, Post $object)
    {
        /**
         * Paging
         **/
        $offset = $request->get('start', 0);
        $limit = $request->get('length', 10);
        $paged = ($offset + $limit) / $limit;
        Paginator::currentPageResolver(function () use ($paged) {
            return $paged;
        });

        $records = [];
        $records["data"] = [];

        /*Group actions*/
        if ($request->get('customActionType', null) == 'group_action') {
            $records["customActionStatus"] = "danger";
            $records["customActionMessage"] = "Group action did not completed. Some error occurred.";
            $ids = (array) $request->get('id', []);
            $customActionValue = $request->get('customActionValue', 0);
            switch ($customActionValue) {
                case 'set_as_popular':{
                        $result = $object->updateMultiple($ids, [
                            'is_popular' => 1,
                        ], true);
                    }break;
                case 'unset_as_popular':{
                        $result = $object->updateMultiple($ids, [
                            'is_popular' => 0,
                        ], true);
                    }break;
                default:{
                        $result = $object->updateMultiple($ids, [
                            'status' => $customActionValue,
                        ], true);
                    }break;
            }
            if (!$result['error']) {
                $records["customActionStatus"] = "success";
                $records["customActionMessage"] = "Group action has been completed.";
            }
        }

        /*
         * Sortable data
         */
        $orderBy = $request->get('order')[0]['column'];
        switch ($orderBy) {
            case 1:{
                    $orderBy = 'id';
                }
                break;
            case 2:{
                    $orderBy = 'global_title';
                }
                break;
            case 3:{
                    $orderBy = 'status';
                }
                break;
            case 4:{
                    $orderBy = 'order';
                }
                break;
            case 5:{
                    $orderBy = 'created_by';
                }
                break;
            default:{
                    $orderBy = 'created_at';
                }
                break;
        }
        $orderType = $request->get('order')[0]['dir'];

        $getByFields = [];
        if ($request->get('global_title', null) != null) {
            $getByFields['global_title'] = ['compare' => 'LIKE', 'value' => $request->get('global_title')];
        }
        if ($request->get('status', null) != null) {
            $getByFields['status'] = ['compare' => '=', 'value' => $request->get('status')];
        }

        $items = $object->searchBy($getByFields, [$orderBy => $orderType], true, $limit);

        $iTotalRecords = $items->count();
        $sEcho = intval($request->get('sEcho'));

        foreach ($items as $key => $row) {
            $status = '<span class="label label-success label-sm">Activated</span>';
            if ($row->status != 1) {
                $status = '<span class="label label-danger label-sm">Disabled</span>';
            }
            $popular = '';
            if ($row->is_popular != 0) {
                $popular = '<span class="label label-success label-sm">Popular</span>';
            }

            /*Edit link*/
            $link = asset($this->adminCpAccess . '/' . $this->routeLink . '/edit/' . $row->id . '/' . $this->defaultLanguageId);
            $removeLink = asset($this->adminCpAccess . '/' . $this->routeLink . '/delete/' . $row->id);

            $records["data"][] = array(
                '<input type="checkbox" name="id[]" value="' . $row->id . '">',
                $row->id,
                $row->global_title,
                $status,
                $row->order,
                $popular,
                $row->created_at->toDateTimeString(),
                '<a class="fast-edit" title="Fast edit">Fast edit</a>',
                '<a href="' . $link . '" class="btn btn-outline green btn-sm"><i class="icon-pencil"></i></a>' .
                '<button type="button" data-ajax="' . $removeLink . '" data-method="DELETE" data-toggle="confirmation" class="btn btn-outline red-sunglo btn-sm ajax-link"><i class="fa fa-trash"></i></button>',
            );
        }

        $records["sEcho"] = $sEcho;
        $records["iTotalRecords"] = $iTotalRecords;
        $records["iTotalDisplayRecords"] = $iTotalRecords;

        return response()->json($records);
    }

    public function postFastEdit(Request $request, Post $object)
    {
        $data = [
            'id' => $request->get('args_0', null),
            'global_title' => $request->get('args_1', null),
            'order' => $request->get('args_2', null),
        ];

        $result = $object->fastEdit($data, false, true);
        return response()->json($result, $result['response_code']);
    }

    public function getEdit(Request $request, Post $object, $id, $language)
    {
        $dis = [];

        $oldInputs = old();
        if ($oldInputs && $id == 0) {
            $oldObject = new \stdClass();
            foreach ($oldInputs as $key => $row) {
                $oldObject->$key = $row;
            }
            $dis['object'] = $oldObject;
        }

        $currentEditLanguage = Models\Language::getBy([
            'id' => $language,
            'status' => 1,
        ]);
        if (!$currentEditLanguage) {
            $this->_setFlashMessage('This language it not supported', 'error');
            $this->_showFlashMessages();
            return redirect()->back();
        }
        app()->setLocale($currentEditLanguage->default_locale);

        $dis['currentEditLanguage'] = $currentEditLanguage;

        $dis['rawUrlChangeLanguage'] = asset($this->adminCpAccess . '/' . $this->routeLink . '/edit/' . $id) . '/';

        $checkedNodes = [];

        if (!$id == 0) {
            $item = $object->find($id);
            /*No page with this id*/
            if (!$item) {
                $this->_setFlashMessage('Item not exists.', 'error');
                $this->_showFlashMessages();
                return redirect()->back();
            }
            $checkedNodes = $item->category()->getRelatedIds()->toArray();

            $item = $object->getById($id, $language, [
                'status' => null,
                'global_status' => null,
            ]);
            /*Create new if not exists*/
            if (!$item) {
                $item = new PostContent();
                $item->language_id = $language;
                $item->created_by = $this->loggedInAdminUser->id;
                $item->post_id = $id;
                $item->save();
                $item = $object->getById($id, $language, [
                    'status' => null,
                    'global_status' => null,
                ]);
            }
            $dis['object'] = $item;
            $this->_setPageTitle('Edit post', $item->global_title);

            $args = array(
                'user_type' => $this->loggedInAdminUser->adminUserRole->id,
                'user' => $this->loggedInAdminUser->id,
                'post_template' => $item->page_template,
                'model_name' => 'Post',
                'post_with_related_category_id' => $checkedNodes,
            );
            $customFieldBoxes = new Acme\CmsCustomField();
            $customFieldBoxes = $customFieldBoxes->getCustomFieldsBoxes($item->id, $args, 'post');
            $dis['customFieldBoxes'] = $customFieldBoxes;
        }

        $dis['categoriesHtml'] = $this->_getCategories(0, $checkedNodes);

        return $this->_viewAdmin('posts.edit', $dis);
    }

    public function postEdit(Request $request, Post $object, PostMeta $objectMeta, $id, $language)
    {
        $data = $request->all();
        if (!$data['slug']) {
            $data['slug'] = str_slug($data['title']);
        }

        if ($id == 0) {
            $data['created_by'] = $this->loggedInAdminUser->id;
            $result = $object->createItem($language, $data);
        } else {
            $result = $object->updateItemContent($id, $language, $data);
        }

        if ($result['error']) {
            $this->_setFlashMessage($result['message'], 'error');
            $this->_showFlashMessages();

            if ($id == 0) {
                return redirect()->back()->withInput();
            }

            return redirect()->back();
        }

        $this->_setFlashMessage($result['message'], 'success');
        $this->_showFlashMessages();

        /*Save completed*/
        $customFields = json_decode($request->get('custom_fields'));
        $this->_saveContentMeta($result['object']->id, $customFields, $objectMeta);

        if ($id == 0) {
            return redirect()->to(asset($this->adminCpAccess . '/' . $this->routeLink . '/edit/' . $result['object']->post_id . '/' . $language));
        }
        return redirect()->back();
    }

    public function deleteDelete(Request $request, Post $object, $id)
    {
        $result = $object->deleteItem($id);
        return response()->json($result, $result['response_code']);
    }

    private function _getCategories($parent = 0, $checkedNodes = [])
    {
        $result = '';
        $nodes = Category::getBy([
            'parent_id' => $parent,
        ], [
            'global_title' => 'ASC',
        ], true);
        if ($nodes->count() > 0) {
            $result .= '<ul class="list-unstyled">';
            foreach ($nodes as $key => $row) {
                $categoryTitle = $row->global_title;

                $result .= '<li>';
                $result .= '<label><input type="checkbox" ' . ((in_array($row->id, $checkedNodes)) ? 'checked="checked"' : '') . ' name="category_ids[]" value="' . $row->id . '">' . $categoryTitle . '</label>';
                $result .= $this->_getCategories($row->id, $checkedNodes);
                $result .= '</li>';
            }
            $result .= '</ul>';
        }
        return $result;
    }
}
