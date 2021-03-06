<?php
namespace sibilino\yii2\openlayers;

use yiiunit\TestCase;
use yii\web\View;
use yii\web\JsExpression;

class OpenLayersTest extends TestCase
{
	protected function setUp()
	{
		parent::setUp();
		$this->mockApplication([
			'vendorPath' => __DIR__.'/../../../..',
			'components' => [
				'assetManager' => [
					'basePath' => __DIR__.'/../../../../../assets',
					'baseUrl' => 'http://localhost/tester2/assets',
				],
			],
			'aliases' => [
				'@sibilino/yii2/openlayers' => __DIR__.'/../widget', 
			],
		]);
	}
	
	/* @var $widget OpenLayers */
	public function testInit()
	{
		$widget = OpenLayers::begin();
		$this->assertTrue(isset($widget->options['id']));
		$this->assertTrue(isset($widget->jsVarName));
		$this->assertArrayHasKey('sibilino\yii2\openlayers\OpenLayersBundle', $widget->view->assetBundles);
	}
	
	public function testRun()
	{
		$this->expectOutputString('<div id="test" class="map"></div>');
		$widget = OpenLayers::begin([
			'id' => 'test',
			'scriptPosition' => View::POS_LOAD,
			'options' => [
				'class' => 'map',
			],
		]);
		OpenLayers::end();
		$this->assertArrayHasKey(View::POS_LOAD, $widget->view->js);
	}
	
	/**
	 * @dataProvider optionProvider
	 */
	public function testOptions($options, $outputRegExp)
	{
		$widget = OpenLayers::begin($options);
		OpenLayers::end();
		$this->assertRegExp("/$outputRegExp/", $this->getLastScript($widget));
	}
	
	public function optionProvider()
	{
		return [
			[ // modified jsVarName
				[
					'jsVarName' => 'testmap',
				],
				'^var testmap = new ol.Map\('
			],
			[ // Simplified View
				[
					'mapOptions' => [
						'view' => [
							'center' => new JsExpression('ol.proj.transform([37.41, 8.82], "EPSG:4326", "EPSG:3857")'),
							'zoom' => 4,
						],
					],
				],
				'view"?: ?new ol.View\({[^\w]*center"?: ?ol.proj.transform\(\[37.41, 8.82\], "EPSG:4326", "EPSG:3857"[^\w]*zoom"?: ?4[^\w]*}\)'
			],
			[ // Custom View
				[
					'mapOptions' => [
						'view' => new OL('View', ['center'=>[0, 0], 'zoom'=>2]),
					],
				],
				'view"?: ?new ol.View\({[^\w]*center"?: ?\[0, ?0\][^\w]*zoom"?: ?2[^\w]*}\)'
			],
			[ // Simplified Layers
				[
					'mapOptions' => [
						'layers' => [
							'Tile' => 'OSM',
						],
					],
				],
				'layers"?: ?\[[^\w]*new ol.layer.Tile\({[^\w]*source"?: ?new ol.source.OSM\(\)[^\w]*\]'
			],
			[
				[
					'mapOptions' => [
						'layers' => [
							'Tile' => [
								'visible' => false,
							],
						],
					],
				],
				'layers"?: ?\[[^\w]*new ol.layer.Tile\({[^\w]*visible"?: ?false[^\w]*\]'
			],
			[
				[
					'mapOptions' => [
						'layers' => [
							'Tile' => [
								'source' => new OL('source.MapQuest', [
									'layer' => 'sat',
								])
							],
						],
					],
				],
				'layers"?: ?\[[^\w]*new ol.layer.Tile\({[^\w]*source"?: ?new ol.source.MapQuest\([^\w]*layer"?: ?"sat"[^\w]\)[^\w]*\]'
			],
			[ // Custom Layers
				[
					'mapOptions' => [
						'layers' => [
							new OL('layer.Tile', ['source'=>'osmsource']),
						],
					],
				],
				'layers"?: ?\[[^\w]*new ol.layer.Tile\({[^\w]*source"?: ?"osmsource"[^\w]*\]'
			],
		];
	}
		
	/**
	 * @param DygraphsWidget $widget
	 * @return string
	 */
	private function getLastScript($widget) {
		$scripts = $widget->view->js[$widget->scriptPosition];
		return end($scripts);
	}
}