<?php

class PersonalisationAdmin extends ModelAdmin
{

    public static $url_segment = 'personalisation';

    public static $menu_title = 'Personalisation';

    public static $managed_models = array('BasicPersonalisation');
    

    public static function managed_personalisation_models()
    {
        $classes = array();
        foreach (ClassInfo::subclassesFor('PersonalisationScheme') as $i => $class) {
            if ($class == 'PersonalisationScheme') {
                continue;
            }
            if (ClassInfo::classImplements($class, 'TestOnly')) {
                continue;
            }

            $tempObj = singleton($class);
            if (!$tempObj::$is_abstract) {
                $classes[] = $class;
            }
        }
        
        return $classes;
    }

    public static function managed_variation_models()
    {
        $classes = array();
        foreach (ClassInfo::subclassesFor('PersonalisationVariation') as $i => $class) {
            if ($class == 'PersonalisationVariation') {
                continue;
            }

            if (ClassInfo::classImplements($class, 'TestOnly')) {
                continue;
            }

            //if(singleton($class)->canCreate()) $classes[] = $class;
            $classes[] = $class;
        }
        return $classes;
    }

    /**
     * @todo Are we using this? get rid of it
    **/
    public function getList()
    {
        $context = $this->getSearchContext();
        $params = $this->request->requestVar('q');
        $list = $context->getResults($params);
        
        $this->extend('updateList', $list);

        return $list;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $tempList = PersonalisationScheme::get();
        $list = new ArrayList();

        
        if ($tempList->Count() != 0) {
            foreach ($tempList as $e) {
                $list->push($e);
            }
        } else {
            $list = new DataList('BasicPersonalisation');
        }

        $listField = GridField::create(
            $this->sanitiseClassName($this->modelClass),
            false,
            $list,
            $fieldConfig = GridFieldConfig_RecordEditor_Personalisation::create($this->stat('page_length'))
                ->removeComponentsByType('GridFieldFilterHeader')
        );

        // Validation
        if (singleton($this->modelClass)->hasMethod('getCMSValidator')) {
            $detailValidator = singleton($this->modelClass)->getCMSValidator();
            $listField->getConfig()->getComponentByType('GridFieldDetailForm')->setValidator($detailValidator);
        }

        $form = new Form(
            $this,
            'EditForm',
            new FieldList($listField),
            new FieldList()
        );
        $form->addExtraClass('cms-edit-form cms-panel-padded center');
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $editFormAction = Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'EditForm');
        $form->setFormAction($editFormAction);
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');

        $this->extend('updateEditForm', $form);
        
        return $form;
    }

    public function init()
    {
        LeftAndMain::init();

        $models = $this->getManagedModels();

        if ($this->request->param('ModelClass')) {
            $this->modelClass = $this->unsanitiseClassName($this->request->param('ModelClass'));
        } else {
            reset($models);
            $this->modelClass = key($models);
        }
        
        Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/ModelAdmin.js');
        Requirements::css("personalisation/css/personalisationAdmin.css");
        Requirements::javascript("personalisation/thirdparty/flot-0.7/jquery.flot.js");
        Requirements::javascript("personalisation/thirdparty/flot-0.7/jquery.flot.time.js");
        Requirements::javascript("personalisation/thirdparty/flot-0.7/jquery.flot.selection.js");
        Requirements::javascript("personalisation/thirdparty/flot-0.7/jquery.flot.resize.js");
        Requirements::customScript(
            "var personalisationReportsBase='" . PersonalisationReportController::$base_link . "';"
        );
    }

    public function getReport()
    {
    }

    public function Breadcrumbs($unlinked = false)
    {
        return new ArrayList();
    }

    public function Backlink()
    {
        return 'admin/personalisation';
    }
}

class GridFieldConfig_RecordEditor_Personalisation extends GridFieldConfig
{
    /**
     *
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage=null)
    {
        $this->addComponent(new GridFieldButtonRow('before'));
        $this->addComponent(new GridFieldAddNewButton_Personalisation('buttons-before-left'));
        $this->addComponent(new GridFieldToolbarHeader());
        $this->addComponent($sort = new GridFieldSortableHeader());
        $this->addComponent($filter = new GridFieldFilterHeader());
        $this->addComponent(new GridFieldDataColumns());
        $this->addComponent(new GridFieldEditButton());
        //$this->addComponent(new GridFieldDeleteAction());
        $this->addComponent(new GridFieldPageCount('toolbar-header-right'));
        $this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));

        $gridDetailForm = new GridFieldDetailForm();
        $gridDetailForm->setItemRequestClass('GridFieldDetailFormPersonalisation_ItemRequest');
        $this->addComponent($gridDetailForm);

        $sort->setThrowExceptionOnBadDataType(false);
        $filter->setThrowExceptionOnBadDataType(false);
        $pagination->setThrowExceptionOnBadDataType(false);
    }

    public function getReports()
    {
        return "foo";
    }
}

class GridFieldDetailFormPersonalisation_ItemRequest extends GridFieldDetailForm_ItemRequest
{


    public function __construct($gridField, $component, $record, $popupController, $popupFormName)
    {
        $params = Controller::curr()->request->allParams();
        if ($params['ID'] == 'new') {
            $record = new $params['ModelClass'];
        }
        $this->gridField = $gridField;
        $this->component = $component;
        $this->record = $record;
        $this->popupController = $popupController;
        $this->popupFormName = $popupFormName;
        parent::__construct($gridField, $component, $record, $popupController, $popupFormName);
    }
}

class GridFieldAddNewButton_Personalisation implements GridField_HTMLProvider
{

    protected $targetFragment;

    protected $buttonName;

    public function setButtonName($name)
    {
        $this->buttonName = $name;
        return $this;
    }

    public function __construct($targetFragment = 'before')
    {
        $this->targetFragment = $targetFragment;
    }

    public function getHTMLFragments($gridField)
    {
        if (!$this->buttonName) {
            $objectName = singleton($gridField->getModelClass())->i18n_singular_name();
            $this->buttonName = _t('GridField.Add', 'Add {name}', array('name' => $objectName));
        }
        $managedClasses = PersonalisationAdmin::managed_personalisation_models();
        
        $buttons = new ArrayList();
        foreach ($managedClasses as $managedClass) {
            $gridField->setModelClass($managedClass);
            $gridField->getForm()->setFormAction('admin/personalisation/'.$managedClass.'/EditForm/');
            $gridField->setName($managedClass);
            
            $buttons->push(new ArrayData(array(
                'NewLink' => Controller::join_links($gridField->Link('item'), 'new'),
                'ButtonName' => ucwords(trim(strtolower(preg_replace('/_?([A-Z])/', ' $1', $managedClass))))
            )));
        }

        $data = new ArrayData(array(
            'Buttons' => $buttons
        ));
        
        return array(
            $this->targetFragment => $data->renderWith('GridFieldAddNewbutton_Personalisation'),
        );
    }
}
