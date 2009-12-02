<?php
/**
 * Datatable Component. 
 *
 * @package plugin.yui
 */
/**
* 
*/
class DatatableComponent extends Object
{
	
	var $controller;	
	var $components = array('RequestHandler');
	
	var $output = array();
	
	public function initialize(&$controller, $settings=array()) {
		$this->controller =& $controller;
	}
	
	/**
	 * Check for YUI Ajax Req, hijack
	 *
	 * @return void
	 **/
	public function startup(&$controller) {
		if($this->RequestHandler->isAjax()) {
			Configure::write('debug', 0);
			$controller->layoutPath = '../../plugins/yui/views/layouts';
			$controller->layout = 'ajax';
			$controller->RequestHandler->ajaxLayout = 'ajax';
			$controller->autoRender = false;
		}
	}
	
	/**
	 * For now, assume data name maps to the controller name. 
	 *
	 * @return void
	 **/
	public function shutdown(&$controller) {
		if($controller->RequestHandler->isAjax()) {
			$controller->output = $controller->render('/../plugins/yui/views/elements/ajax');
		}
	}
	
	/**
	 * Used when making yui-datatables. 
	 * Call this instead of $this->paginate() in the controller.
	 * Preps data for making <table>
	 * The goal is to flatten the data
	 * field => value instead of model => array('field'=>'value','field'=>'value'), model => array('field'=>'value','field'=>'value')
	 * @todo On second thought, might not be necessary.
	 * 
	 * @param string $model Name of model
	 * 			@todo actually allow for Model. Right now gathering paging info here to output, 
	 * 					so need model name for key to controller->params[paging][Model]
	 * @param array $options.
	 * 					exclude - array of fields to exclude ie: Category.id, Product.id
	 * 					@todo include - array of fields to include. Naturally, include and Exclude are mutually exclusive
	 * @todo allow all the Pagination args to be passed in. maybe in options? allow 'scope' and 'whitelist' which can be passed to parent pagination 
	 * @return void
	 **/
	function paginate($model, $options = array()) {
		//@bug todo
		//turn rather do all the . to _ on the php side for now. 
		//so, fix the sort key that comes in.
		if(isset($this->controller->passedArgs['sort'])) {
			$tSort = $this->controller->passedArgs['sort'];
			$tSort = explode('_', $tSort, 2);
			$this->controller->passedArgs['sort'] = implode('.', $tSort);
		}
		$results = $this->controller->paginate($model);
		
		//Add in actions.
		$newResults = array();
		foreach($results as $result) {
			$newResults[] = Set::insert($result, 'actions', array('view','edit'));
		}

		$pagingInfo = $this->controller->params['paging'][$model];

		$data = array(
			'model' => $model,
			'recordsReturned' => $pagingInfo['current'],
			'totalRecords' => $pagingInfo['count'],
			'pageSize' => $pagingInfo['options']['limit'],
			'startIndex' => (($pagingInfo['page']-1) * $pagingInfo['options']['limit']),
			'initialPage' => $pagingInfo['page'],
			'sort' => $this->_getSortField($pagingInfo['options']['order']), 
			'dir' =>  $this->_getSortDir($pagingInfo['options']['order']),
			'records' => $newResults
		);
		//debug($pagingInfo);
		//This is close to, but not quite, the same as myColumnDefs, so separating their generation now. 
		$data['myDataSourceFields'] = $this->buildDataSourceFields($newResults[0], $options);		
		$data['myColumnDefs'] = $this->buildColumnDefs($newResults[0], $options);

		return $data;
	}
	
	/**
	 * Build the array of fields for the DS
	 *
	 * @return void
	 **/
	function buildDataSourceFields($record, $options) {
		if(isset($record['actions'])) {
			$record['actions'] = 'actions'; //for the label. 
		}

		$keys = array_keys(Set::flatten($record));
		$output = array();

		foreach ($keys as $key) {
			$field['key'] = $key;
//			$field['parser'] = '';
			$output[] = $field;
		}

		return $output;
	}
	
	/**
	 * Build the column definitions we will need for datatable. 
	 * Exclusions should be dealt with here me thinks. Set up formatters as well
	 * Generate the Column Definitions array. based on dt expectations
	 * //structure:
	 * //array('id'=>'foo', 'label'=>'Foo')
	 * //options available
	 * //array('id'=>'foo', 'label'=>'Foo', sortable=>true)
	 * yes, def need to work out exclusion system at this point. There is certain info, like ID we want 
	 * to have in the DS even though we dont want to display it
	 * @param array a db row. generally first row of result set. 
	 * @return array
	 **/
	function buildColumnDefs($record, $options) {

		$exclude = false;
		if(isset($options['exclude'])) {
			$exclude = $options['exclude'];
			if(is_string($exclude)) { $exclude = array($exclude); }
		}

		if(isset($record['actions'])) {
			$record['actions'] = 'actions'; //for the label. 
		}
		$keys = array_keys(Set::flatten($record));
		
		$output = array();
		$used = array();
		foreach ($keys as $key) {
			if(in_array($key, $exclude)) { continue; }
			
			$label = explode('.', $key, 2);

			if(isset($label[1]) && in_array($label[1], $used)) {
				$label = implode(' ', $label);
			} else {
				$label = isset($label[1]) ? $label[1] : $label[0];
			}
			
			$sortable = ('actions' != $key);

			$output[] = array(
				//Do we have to remove the . ? 
				//May cause trouble, what with that being how to access methods/props in js
				'key' => str_replace('.', '_',$key), 
				'label' => $label,
				'field' => $key,
				'sortable' => $sortable
			);
			$used[] = $label;
		}

		return $output;
	}
	
	/**
	 * Figure out the main sorting field. 
	 *
	 * @return string
	 **/
	function _getSortField($order) {
		return str_replace('.', '_', key($order));
	}

	/**
	 * Figure out the main sorting directions. 
	 *
	 * @return string
	 **/
	function _getSortDir($order) {
		return strtolower(current($order));
	}
	
}

