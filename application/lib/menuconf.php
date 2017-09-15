<?php 
/**
 * zentrale Menükonfiguration
 */
global $arrMenu;

$arrMenu = array(

	// Index Menue -> Einsatzberichte
	/*'index' => array(
		'title' 	=> _('Rettungsdienst'),
		'class'		=> 'fa-ambulance',
		'submenu' => array(
			'general' => array(
					'title' => _('Alle anzeigen'),
					'action' => 'index'
			),
			'storno' => array(
					'title' => _('storniert'),
					'action' => 'storno'
			),
			'finish' => array(
					'title' => _('beendet'),
					'action' => 'finish'
			),
			'archiv' => array(
					'title' => _('abgeschlossen'),
					'action' => 'archiv'
			),
			'alarm' => array(
					'title' => _('alarmiert'),
					'action' => 'alarm'
			)
		)
	),*/
	
	// Index Menue -> Einsatzberichte
	'report' => array(
		'title' 	=> _('Feuerwehr'),
		'class'		=> 'fa-file-text-o',
		'submenu' => array(
			'open' => array(
					'title' => _('Offene Einsätze'),
					'action' => 'index'
			),
			'edit' => array(
					'title' => _('in Bearbeitung'),
					'action' => 'edit'
			),
			'finish' => array(
					'title' => _('Abgeschlossen'),
					'action' => 'finish'
			)
			/*
			,
			'archiv' => array(
					'title' => _('Archiv'),
					'action' => 'archiv'
			)
			*/
		)
	),

	// Benutzer Menue
	'user' => array(
		'title' 		=> _('Benutzer'),
		'class'			=> 'fa-user',
		'admin_only'	=> true, // nur für admins sichtbar
		'submenu' 		=> array(
			'view' => array(
					'title' 	=> _('Alle anzeigen'),
					'action' 	=> 'index',
			),

			'add' => array(
					'title' 	=> _('Neu'),
					'action' 	=> 'add',
			)
		)
	),

	// Config Menue
	'config' => array(
		'title' 	=> _('Einstellungen'),
		'class'		=> 'fa-sliders',
		'admin_only'	=> true, // nur für admins sichtbar
		'submenu' => array(
			'general' => array(
					'title' => _('Allgemein'),
					'action' => 'index',
			)
		)
	)
);
?>