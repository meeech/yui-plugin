<?php

class DatatableHelper extends AppHelper {
	
	var $helpers = array('Html', 'Javascript');

	/**
	 * generate the html nice and accessible table yo!
	 *
	 * Basing data in on yui datasource req. maps to that, since we want to output that anyhow. 
	 * 
	 * seems to make the most sense.
	 * See http://developer.yahoo.com/yui/examples/datatable/dt_clientpagination.html
	 * 
	 * 
	 * @return void
	 **/
	function table($data, $options = array()) {

		$id = uniqid('datatable');
		//Was basing it on data[myColumnDefs] but that caused issues when generating table for non-js people.
		//This is more straightforward, generated from data passed in.
		//Append Actions
		if(isset($data['actions'])) {
//			Set::insert()
		}

		$heads = $this->tableHeaders($data['myColumnDefs']);
		$rows = $this->tableCells($data);
		$output = sprintf('<div class="plank-datatable"><table id="%3$s">%1$s%2$s</table></div>', $heads,$rows,$id);

		$output .= $this->jsTableData($data, $id);
		
		return $this->output($output);

	}

	/**
	 * generate any info we will need for JS to hook into this table. 
	 * Basically JSONs up the data for the table. 
	 *
	 * @return void
	 **/
	function jsTableData($data, $id) {
		
		//Add in the dataurl.
		$data['dataUrl'] = $this->Html->url('', true) . '/';

		$prefix = 'YAHOO.namespace("Plank.bb.datatable").'.$id.' = ';
		$postfix = ' ;';
		$out = $this->Javascript->object($data, 
			array('block'=>true, 'prefix'=>$prefix, 'postfix'=>$postfix)
		);
		return $out;
	}

	/**
	 * May just override HTML::tableHeaders if we need to be able to specify special class on specific TH as oppossed to all. 
	 * @todo expand to handle different data being passed in. right now, assume everything passed in nicely. 
	 * @return string
	 **/
	function tableHeaders($data) {
		$headerAr = Set::extract($data, '{n}.label');
		$result = '<thead>' . $this->Html->tableHeaders($headerAr) . '</thead>';
		return $result;
	}
	
	/**
	 * Create the tablerows+cells. 
	 *
	 * @return string
	 **/
	function tableCells($data) {
		//headers... this fixes table for non-js people/generation. so we know what the ignores are.
		$headerFields = Set::extract($data['myColumnDefs'], '{n}.field');

		//tear through, dumping fields we don't want, and generate action links
		foreach($data['records'] as &$record) {
			if(isset($record['actions'])) {
				$record['actions'] = $this->genActions($record['actions'], $record[Inflector::classify($this->params['controller'])]['id']);
			}

			//skin out ones we don't want
			$new =  Set::flatten($record);
			$record = $new;
			foreach ($record as $key => $value) {
				if(!in_array($key, $headerFields)) {
					unset($record[$key]);
					continue;
				}
			}
		}

		$result = $this->Html->tableCells($data['records'], array('class'=>'yui-dt-odd'), array('class'=>'yui-dt-even'));
		$result = '<tbody>' . $result . '</tbody>';
		return $result;
	}
	
	/**
	 * Generated the Action links for table.
	 *
	 * @return void
	 **/
	function genActions($actions, $id) {
//		var_dump($actions);
		$a = array();
		foreach ($actions as $action) {
			$a[] = $this->Html->link(ucfirst($action), compact('action', 'id')); 
		}
		
		return implode(' ', $a);
	}

}