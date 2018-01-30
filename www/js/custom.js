/*******************************************************************************
 * File: custom.js Project: Einsatzberichte System Author: David Grimmer
 ******************************************************************************/

// array aller Input- Felder der "edit" Formulare
var arrFormInputs = new Array();

//array aller Textarea- Felder der "edit" Formulare
var arrFormTextarea = new Array();

// zeichenkette über vorhandene Values der edit input felder
var strChecksum = '';

//zeichenkette über vorhandene Values der edit textarea felder
var strTextarea = '';
	
$(document).ready(function(){

	// Datepicker Felder Init
	initDatePicker();
	
	// aktiven Seiten TAB bei reload erhalten
	if ($.cookie('active_tab'))
	{
		var strID 	= $.cookie('active_tab');
		var strTab 	= $('#' + strID).children().attr('class');

		$('.switch-tab').removeClass('active');
		$('#' + strID).addClass('active');
		$('.report-content-wrap').hide();
		$('#' + strTab).show();

	}
	
	// Tooltipps
	$('.tooltip').bind('mouseenter', function(){
		
		switch ($(this).attr('id'))
		{
			case 'tooltip_brandumfang':
				$(this).tooltip({
					
					content: 	'<p>Kleinbrand A = Kleinlöschgeräte</p>' + 
								'<p>Kleinbrand B = bis 1 C- Rohr</p>' +
								'<p>Mittelbrand = bis 2 C- Rohre</p>' +
								'<p>Großbrand = ab 3 C- Rohre</p>'
					
				});
				break;
		
		}
		
	});

	// Validierung des Datums (muss in der Vergangeheit liegen)
	$('.datepicker').on('change', function() {
		
		var arrDate = $(this).val().split('.');
		var objDate = new Date(arrDate[1] + '/' + arrDate[0] + '/'+arrDate[2]);
		var objToday = new Date();

		// Zeiten des Datums auf null setzen
		objToday.setHours(0); objToday.setMinutes(0); objToday.setSeconds(0); objToday.setMilliseconds(0);

		if (objDate <= objToday)
		{
			// nichts tun... Datum wird übernommen
			return;
		}
		else
		{
			alert('Datum muss in der Vergangenheit liegen!');

			// Datum auf "heute" stetzen
			$(this).datepicker('setDate', new Date());
			$(this).datepicker().close();
		}

	});

	// Formular absenden
	$('#report-submit-save').click(function(){ $('.report-save-form').submit() });

	// Umschalten der Seiten Tabs
	$('.switch-tab').bind('click', function(){

		if (!$(this).hasClass('active'))
		{

			var eID = $(this).children('span').attr('class');

			$('.switch-tab').removeClass('active');
			$('.report-content-wrap').hide();

			$('#' + eID).show();
			$.cookie('active_tab', $(this).attr('id'));
			$(this).addClass('active');
			
		}
		return false;
		
	})

	// hinzufügen weiterer Fahrzeuge
	$('.add-resource').click(function(){

		var strHTML 	= $('table.resourcen-overview tbody').html();
		var intIndex 	= parseInt($(this).attr('id').replace('add_index_', ''));
		var addHTML 	= $('.table-add-report-resource tbody').html();

		// neuen HTML Inhalt "zusammenbauen" und einsetzen
		addHTML = addHTML.replace(/###index###/g, $(this).attr('id').replace('add_index_', ''));
		$('table.resourcen-overview tbody').html(strHTML + addHTML);

		// Index hochzählen
		intIndex = intIndex + 1;
		$(this).attr('id', 'add_index_' + intIndex);

		initDatePicker();

		return false;
		
	})

	// hinzufügen Personal
	$('.add-personal').click(function(){

		var strHTML 	= $('table.personal-overview tbody').html();
		var intIndex 	= parseInt($(this).attr('id').replace('add_index_', ''));
		var addHTML 	= $('.table-add-report-personal tbody').html();

		// neuen HTML Inhalt "zusammenbauen" und einsetzen
		addHTML = addHTML.replace(/###index###/g, $(this).attr('id').replace('add_index_', ''));
		$('table.personal-overview tbody').html(strHTML + addHTML);

		// Index hochzählen
		intIndex = intIndex + 1;
		$(this).attr('id', 'add_index_' + intIndex);

		$('.calc-personal').bind('change', function(){
			calcPersonal();
		})

		return false;
		
	})

	calcActionTime(); // setzt die Einsatzzeit beim laden des Einsatzes
	calcAusgerueckt(); // setzt die Anzahl der ausgerückten Kräfte beim laden
						// der Seite

	// ruft die Funktionen beim ändern der Felder erneut auf
	$('.calc-personal').bind('change', function(){
		calcPersonal();
		calcActionTime()
	})
	
	// ruft die Funktionen beim ändern der Felder erneut auf
	$('.calc-aus').bind('change', function(){
		calcAusgerueckt();
	})
	
	// ruft die Funktionen beim ändern der Felder erneut auf
	$('.personen_rettung').bind('change', function(){
		calcRettungAnzahl();
	})
	
	// ruft die Funktionen beim ändern der Felder erneut auf
	$('.personal_schaeden').bind('change', function(){
		calcAnzahlPersonalSchaeden();
	})

	// ruft die Funktionen beim ändern der Felder erneut auf
	$('.personen_schaeden').bind('change', function(){
		calcAnzahlPersonenSchaeden();
	})		

	// verleiht den checkboxen die Funktionalität eines Radiobuttons
	$('.radiocheck').bind('click', function(){
		
		var arr_eID = $(this).attr('id').split('_');

		if (arr_eID[1] == 'brandumfang' || arr_eID[1] == 'fehlalarm')	
		{
			
			// sonderbehandlung Brandumfang u. Fehlalarm
			if (!$(this).prop('checked'))
			{
				$(this).prop('checked', false);
				return;
			}
			
			$('.brandumfang').prop('checked', false);
			$('.fehlalarm').prop('checked', false);
		}
		else
		{
			
			if (!$(this).prop('checked'))
			{
				// checkboxgruppe wieder ohne Eintrag = auch der "letzte" Haken
				// kann entfernt werden
				$(this).prop('checked', false);
				return;
			}
			
			$('.' + arr_eID[1]).prop('checked', false);
			
		}
		$(this).prop('checked', true);

	})	
	
	// Schriftfarbe in den Personaltabellen ändern
	$('.color-grey').bind('change', function(){
		
		if ($(this).val() != '0') $(this).removeClass('color-grey');
		if ($(this).val() == '0') $(this).addClass('color-grey');
		
	})
	
	
	/**
	 * bildet die "checksumme" über die vorhandenen Eingaben
	 * 
	 */
	arrFormInputs = $('form.report-save-form').find('input');
	arrFormTextarea = $('form.report-save-form').find('textarea');
	
	// input felder
	arrFormInputs.each(function(index){
		
		if ($(this).attr('type') == 'checkbox')
		{
			if ($(this).prop('checked')) strChecksum = strChecksum + $(this).val();
		}
		else
		{
			strChecksum = strChecksum + $(this).val();
		}

	}) 
	
	// textarea Bereiche
	arrFormTextarea.each(function(){
		
		strTextarea = strTextarea + $(this).val();
		
	}) // "checksumme" über die vorhandenen Eingaben
	
	// zusammenführen der Zeichenketten
	strChecksum = strChecksum + strTextarea;
	
	
}) // $(document).ready(function()

/**
 * Formatierungen DatePicker
 * 
 * @returns
 */ 
function initDatePicker()
{

	// Datepicker Settings
	$('.datepicker').datepicker({
		dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
        dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
        firstDay: 1,
		dateFormat: "dd.mm.yy",
	});
	
} // function initDatePicker()

	
/**
 * function zum entfernen der dynamischen Zeilen bei der Resourcenübersicht
 * 
 * @returns false
 */
function remResource(oEL)
{
	var strID = $(oEL).attr('id').replace('rem-resource-', '');

	$('#add-resource-' + strID).remove();

	return false;
	
} // function remResource(oEL)


/**
 * function zum entfernen der dynamischen Zeilen bei der Personalübersicht
 * 
 * @returns
 */
function remPersonal(oEL)
{
	var strID = $(oEL).attr('id').replace('rem-personal-', '');

	$('#add-personal-' + strID).remove();

	calcPersonal();

	return false;
	
} // function remPersonal(oEL)

/**
 * berechnet die Gesamteinsatzzeit
 * 
 * @returns
 */ 
function calcActionTime()
{
	
	//alert($(document).find('input.einsatzzeit-min').length);
	
	var min = 0;
	var day = 0;
	var std = 0;
	var gesMin = 0;
	var gesStd = 0;
	var multiplikator = 0;
	
	$(document).find('input.einsatzzeit-min').each(function(index){

		multiplikator = 0;
		
		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_hoeherer').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_hoeherer').val());

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_gehobener').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_gehobener').val());

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_mittlerer').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_mittlerer').val());

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_ff').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_ff').val());
		
		if (parseInt($(this).val()) > 0) min = Number(min) + (parseInt($(this).val()) * multiplikator);
		
		//debug::alert($(this).attr('id')+' | '+index+' | '+min+' | '+multiplikator);
	})
	
	$(document).find('input.einsatzzeit-std').each(function(index){

		multiplikator = 0;

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_hoeherer').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_hoeherer').val());

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_gehobener').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_gehobener').val());

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_mittlerer').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_mittlerer').val());

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_ff').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_ff').val());
		
		if (parseInt($(this).val()) > 0) std = Number(std) + (parseInt($(this).val()) * multiplikator);
	})
	
	
	
	$(document).find('input.einsatzzeit-day').each(function(index){

		multiplikator = 0;

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_hoeherer').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_hoeherer').val());

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_gehobener').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_gehobener').val());

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_mittlerer').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_mittlerer').val());

		if (parseInt($('#reportpersonal'+ index +'_eingesetzt_ff').val()) > 0)
			multiplikator = Number(multiplikator) + parseInt($('#reportpersonal'+ index +'_eingesetzt_ff').val());
		
		if (parseInt($(this).val()) > 0) day = Number(day) + (parseInt($(this).val()) * multiplikator);
	})

	

	gesMin = parseInt(day * 1440) + parseInt(std * 60) + parseInt(min);

	gesStd = Math.round((gesMin / 60) * 100) / 100;

	// formatieren der Gesamtzeit
	gesStd = gesStd.toString().replace('.', ',');
	
	$('#report_zeit_eingesetzt').val(gesStd); // Formularfeld füllen

	return false;
	
} // function calcActionTime()


/**
 * berechnet die Personalstärke gesamt
 * 
 * @returns
 */ 
function calcPersonal()
{
	var pers = 0;
	
	$(document).find('input.calc-personal').each(function(){
		if (parseInt($(this).val()) > 0) pers = Number(pers) + parseInt($(this).val());
	})
	
	$('#report_anzahl_eingesetzt').val(pers); // Formularfeld füllen
	
	return false;
	
} // function calcPersonal()


/**
 * berechnet die ausgerückte Personalstärke
 */
function calcAusgerueckt()
{
	var pers = 0;

	$(document).find('input.calc-aus').each(function(){
		if (parseInt($(this).val()) > 0) pers = Number(pers) + parseInt($(this).val());
	})
	
	$('#report_anzahl_ausgerueckt').val(pers); // Formularfeld füllen
	
	return false;
	
} // function calcAusgerueckt()


/**
 * berechnet die Anzahl der geretteten Personen
 * 
 * @returns
 */
function calcRettungAnzahl()
{
	var sum = 0;
	$(document).find('input.personen_rettung').each(function(){
		if (parseInt($(this).val()) > 0) sum = Number(sum) + parseInt($(this).val());
	})

	$('#report_personen_rettung_anzahl_gesamt').val(sum); // Formularfeld
															// füllen

	return false; 
	
} // function calcRettungAnzahl()


/**
 * berechnet die Anzahl der geretteten Personen
 * 
 * @returns
 */
function calcAnzahlPersonenSchaeden()
{
	var sum = 0;
	
	$(document).find('input.personen_schaeden').each(function(){
		if (parseInt($(this).val()) > 0) sum = Number(sum) + parseInt($(this).val());
	})

	$('#report_personen_schaeden_anzahl_gesamt').val(sum); // Formularfeld
															// füllen

	return false; 
	
} // function calcAnzahlPersonenSchaeden()


/**
 * berechnet die Anzahl der geretteten Personen
 * 
 * @returns
 */
function calcAnzahlPersonalSchaeden()
{
	var sum = 0;
	
	$(document).find('input.personal_schaeden').each(function(){
		if (parseInt($(this).val()) > 0) sum = Number(sum) + parseInt($(this).val());
	})

	$('#report_personal_schaeden_anzahl_gesamt').val(sum); // Formularfeld
															// füllen

	return false; 
	
} // calcAnzahlPersonalSchaeden()

// verhalten der checkboxen nur für den Abschnitt "Verständigung/ Anwesenheit"
// Seite2 des Formulars
function checked_value(oEL)
{
	var strClass = $(oEL).attr('class');
	var arrClass = strClass.split(' ');
	var checkClass = arrClass[0];
	
	if (!$(oEL).prop('checked'))
	{
		$(oEL).prop('checked', false); return;
	}
	else
	{
		$('.' + checkClass).prop('checked', false);
		
	}
	
	$(oEL).prop('checked', true);
	
}

function leavePage()
{
	
	// Create Base64 Object
	// Quelle: https://scotch.io/tutorials/how-to-encode-and-decode-strings-with-base64-in-javascript
	var Base64 = {_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(e){var t="";var n,r,i,s,o,u,a;var f=0;e=Base64._utf8_encode(e);while(f<e.length){n=e.charCodeAt(f++);r=e.charCodeAt(f++);i=e.charCodeAt(f++);s=n>>2;o=(n&3)<<4|r>>4;u=(r&15)<<2|i>>6;a=i&63;if(isNaN(r)){u=a=64}else if(isNaN(i)){a=64}t=t+this._keyStr.charAt(s)+this._keyStr.charAt(o)+this._keyStr.charAt(u)+this._keyStr.charAt(a)}return t},decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9+/=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_encode:function(e){e=e.replace(/rn/g,"n");var t="";for(var n=0;n<e.length;n++){var r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r)}else if(r>127&&r<2048){t+=String.fromCharCode(r>>6|192);t+=String.fromCharCode(r&63|128)}else{t+=String.fromCharCode(r>>12|224);t+=String.fromCharCode(r>>6&63|128);t+=String.fromCharCode(r&63|128)}}return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}}
	
	// globale Variable "strChecksum" sichern
	checkSum1 = strChecksum;

	// string codieren
	checkSum1 = Base64.encode(checkSum1);
	
	var arrInputs2 		= new Array();
	var arrTextarea2 	= new Array();
	var strInputs2 		= '';
	var strTextarea2 	= '';
	var checkSum2 		= '';
	
	arrInputs2 =  $('form.report-save-form').find('input');
	
	arrInputs2.each(function(index){
		
		if ($(this).attr('type') == 'checkbox')
		{
			if ($(this).prop('checked')) strInputs2 = strInputs2 + $(this).val();
		}
		else
		{
			strInputs2 = strInputs2 + $(this).val();
		}
		
		
	})
	
	arrTextarea2 = $('form.report-save-form').find('textarea');
	
	// textarea Bereiche
	arrTextarea2.each(function(){
		
		strTextarea2 = strTextarea2 + $(this).val();
		
	}) // "checksumme" über die vorhandenen Eingaben
	
	checkSum2 = Base64.encode(strInputs2 + strTextarea2);
	
	if (checkSum1 == checkSum2)
	{
		return;
	}
	else
	{
		
		var r = confirm("Es befinden sich ungespeicherte Änderungen auf der Seite!\nMöchten Sie die Seite wirklich ohne Speichern verlassen?");
		
		if(r)
		{
			return;
		}
		else
		{
			return false;
		}
		
		return false;
		
	}


}
	
