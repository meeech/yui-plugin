<?php
/**
 * YUI Datatable Helper class test
 * 
 * Right now, only tests against yui v2.8
 * 
 * Mock data used mimics the subnode of big collection of 
 * data that will be passed for setup. hence you will see things like 
 * $data['myColumnDefs']
 * 
 * @package cake.plugins.yui
 * @author Mitchell Amihod
 **/
App::import('Helper', array('Html', 'Yui.Datatable'));

class DatatableTestCase extends CakeTestCase {

	var $tabledata = array(
		'myColumnDefs' => array(
			array('key'=>'id', 'label'=>'ID', 'sortable'=>true),
			array('key'=>'name', 'label'=>'Name', 'sortable'=>true)
		),
		'data' => array(
			array('id'=>1, 'name'=>'Zeus'),
			array('id'=>2, 'name'=>'Athena'),
			array('id'=>3, 'name'=>'Mercury')
		)
	);

	function setUp() {
		//ClassRegistry::flush();
	}

	function startTest() {
		$this->datatable = new DatatableHelper();
		$this->datatable->Html = new HtmlHelper();
	}

	function endTest() {
		unset($this->datatable);
	}
	

	/**
	 *
	 * @return void
	 **/
	function testTableHeader() {
		$data['myColumnDefs'] = $this->tabledata['myColumnDefs'];

		$result = $this->datatable->tableHeaders($data['myColumnDefs']);
		$expected = array('<thead','<tr', 
							'<th', 'ID', '/th', '<th', 'Name', '/th', 
							'/tr', '/thead' );

		$this->assertTags($result, $expected);

	}
	
	
	/**
	 *
	 * @return void
	 **/
	function testTableCells() {
		$data = $this->tabledata;

		$result = $this->datatable->tableCells($data);
		$expected = array(
			'<tbody',
			'<tr',
				'<td', '1', '/td', '<td', 'Zeus', '/td',
			'/tr',
			'<tr',
				'<td', '2', '/td', '<td', 'Athena', '/td',
			'/tr',
			'<tr',
				'<td', '3', '/td', '<td', 'Mercury', '/td',
			'/tr',
			'/tbody'
		);

		$this->assertTags($result, $expected);
	}

}