/***************************************
 * File: 	custom.js
 * Project:	Einsatzberichte System
 * Author: 	David Grimmer
 ***************************************/
	
$(document).ready(function(){

	// Datepicker Felder Init
	initDatePicker();
	
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
			$('.brandumfang').prop('checked', false);
			$('.fehlalarm').prop('checked', false);
		}
		else
		{
			$('.' + arr_eID[1]).prop('checked', false);
		}
		$(this).prop('checked', true);

	})	

	
}) // $(document).ready(function()

/**
 * Formatierungen DatePicker
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
 * @returns
 */ 
function calcActionTime()
{

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

	gesMin = parseInt(day * 1440) + parseInt(std * 60) + parseInt(min);

	gesStd = Math.round((gesMin / 60) * 100) / 100;

	// formatieren der Gesamtzeit
	gesStd = gesStd.toString().replace('.', ',');

	$('#report_zeit_eingesetzt').val(gesStd); // Formularfeld füllen

	return false;
	
} // function calcActionTime()

/**
 * berechnet die Personalstärke gesamt
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
 * @returns
 */
function calcRettungAnzahl()
{
	var sum = 0;
	$(document).find('input.personen_rettung').each(function(){
		if (parseInt($(this).val()) > 0) sum = Number(sum) + parseInt($(this).val());
	})

	$('#report_personen_rettung_anzahl_gesamt').val(sum); // Formularfeld füllen

	return false; 
	
} // function calcRettungAnzahl()

/**
 * berechnet die Anzahl der geretteten Personen
 * @returns
 */
function calcAnzahlPersonenSchaeden()
{
	var sum = 0;
	
	$(document).find('input.personen_schaeden').each(function(){
		if (parseInt($(this).val()) > 0) sum = Number(sum) + parseInt($(this).val());
	})

	$('#report_personen_schaeden_anzahl_gesamt').val(sum); // Formularfeld füllen

	return false; 
	
} // function calcAnzahlPersonenSchaeden()

/**
 * berechnet die Anzahl der geretteten Personen
 * @returns
 */
function calcAnzahlPersonalSchaeden()
{
	var sum = 0;
	
	$(document).find('input.personal_schaeden').each(function(){
		if (parseInt($(this).val()) > 0) sum = Number(sum) + parseInt($(this).val());
	})

	$('#report_personal_schaeden_anzahl_gesamt').val(sum); // Formularfeld füllen

	return false; 
	
} // calcAnzahlPersonalSchaeden()
