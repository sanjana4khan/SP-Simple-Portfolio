<?php
/**
* @package com_spsimpleportfolio
* @author JoomShaper http://www.joomshaper.com
* @copyright Copyright (c) 2010 - 2020 JoomShaper
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/

// No Direct Access
defined ('_JEXEC') or die('Restricted Access');

class SpsimpleportfolioRouter extends JComponentRouterView {
	
	protected $noIDs = false;

	public function __construct($app = null, $menu = null){

		$params = JComponentHelper::getParams('com_spsimpleportfolio');
		$this->noIDs = (bool) $params->get('sef_ids', 1);

		$items = new JComponentRouterViewconfiguration('items');
		$items->setKey('catid');
		$this->registerView($items);
		$item = new JComponentRouterViewconfiguration('item');
		$item->setKey('id')->setParent($items);
		$this->registerView($item);

		// generate rules
		parent::__construct($app, $menu);	
		$this->attachRule(new JComponentRouterRulesNomenu($this));
		if ($params->get('sef_advanced', 0)) {
			$this->attachRule(new JComponentRouterRulesMenu($this));
			$this->attachRule(new JComponentRouterRulesStandard($this));
		} else {
			JLoader::register('SpsimpleportfolioRouterRulesLegacy', __DIR__ . '/helpers/legacyrouter.php');
			$this->attachRule(new SpsimpleportfolioRouterRulesLegacy($this));
		}

	}

	// Items
	public function getItemsSegment($id, $query)
	{
		$category = JCategories::getInstance($this->getName())->get($id);

		if ($category)
		{
			$path = array_reverse($category->getPath(), true);
			$path[0] = '1:root';

			if ($this->noIDs)
			{
				foreach ($path as &$segment)
				{
					list($id, $segment) = explode(':', $segment, 2);
				}
			}

			return $path;
		}

		return array();
	}

	// Items id
	public function getItemsId($segment, $query)
	{
		if (isset($query['id']))
		{
			$category = JCategories::getInstance($this->getName(), array('access' => false))->get($query['id']);

			if ($category)
			{
				foreach ($category->getChildren() as $child)
				{
					if ($this->noIDs)
					{
						if ($child->alias == $segment)
						{
							return $child->id;
						}
					}
					else
					{
						if ($child->id == (int) $segment)
						{
							return $child->id;
						}
					}
				}
			}
		}

		return false;
	}

	// Item
	public function getItemSegment($id, $query) {
		if (!strpos($id, ':')) {
			$db = JFactory::getDbo();
			$dbquery = $db->getQuery(true);
			$dbquery->select($dbquery->qn('alias'))
			->from($dbquery->qn('#__spsimpleportfolio_items'))
			->where('id = ' . $dbquery->q($id));
			$db->setQuery($dbquery);

			$id .= ':' . $db->loadResult();
		}

		if ($this->noIDs) {
			list($void, $segment) = explode(':', $id, 2);
			return array($void => $segment);
		}
		return array((int) $id => $id);
	}

	public function getItemId($segment, $query) {

		if ($this->noIDs) {
			$db = JFactory::getDbo();
			$dbquery = $db->getQuery(true);
			$dbquery->select($dbquery->qn('id'))
				->from($dbquery->qn('#__spsimpleportfolio_items'))
				->where('alias = ' . $dbquery->q($segment));
			$db->setQuery($dbquery);
			return (int) $db->loadResult();
		}
		return (int) $segment;
	}
}

/**
 * Users router functions
 *
 * These functions are proxys for the new router interface
 * for old SEF extensions.
 *
 * @param   array  &$query  REQUEST query
 *
 * @return  array  Segments of the SEF url
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function spsimpleportfolioBuildRoute(&$query) {
	$app = JFactory::getApplication();
	$router = new SpsimpleportfolioRouter($app, $app->getMenu());

	return $router->build($query);
}

/**
 * Convert SEF URL segments into query variables
 *
 * @param   array  $segments  Segments in the current URL
 *
 * @return  array  Query variables
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function spsimpleportfolioParseRoute($segments){
	$app = JFactory::getApplication();
	$router = new SpsimpleportfolioRouter($app, $app->getMenu());

	return $router->parse($segments);
}
