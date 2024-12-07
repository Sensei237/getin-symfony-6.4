let Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 5000
});

function setToastTimer(timer) {
    Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: timer
    });
}

function showErrorAlert(msg="Vous devez renseignez correctement tous les champs !", type='error', timer=5000){
    Toast.fire({
        type: type,
        title: msg
    });
    notify();
}

function notify(s=$successSon){
    var e=document.createElement('audio');
    e.setAttribute('src',s);
    e.play();
}

function addActiveClass(eltId, parentId='#vert-tabs-tab') {
    $(parentId+' a.visible').removeClass('active').addClass('disabled').removeClass('text-primary');
    $(parentId+' '+eltId).addClass('active').addClass('text-primary').removeClass('disabled');
}

var success = 'text-success border-success';
var danger = 'text-danger border-danger';
var bgs = 'text-success border-success';
var bgd = 'text-danger border-danger';

$('')

function afficherInformations() {
    addActiveClass('#vert-confirmation')
    $('#confirm-space').html('');
    var naisV = validerInfosNaissance();
    var contactV = validerInfosContact();
    var academiquetV = validerInfosAcademiques();
    var tuteurV = validerInfosTuteur();
    if (!naisV.hasError && !contactV.hasError && !academiquetV.hasError && !tuteurV.hasError) {
        $('#confirm-space').append(naisV.data);
        $('#confirm-space').append(contactV.data);
        $('#confirm-space').append(academiquetV.data);
        $('#confirm-space').append(tuteurV.data);
        $('#vert-tabs-confirm-tab').trigger('click');
    }else {
        showErrorAlert();
        if (naisV.hasError){         
            $('#first-prev').trigger('click');
        }else {
            if (contactV.hasError) {
                $('#second-prev').trigger('click');
            }else {
                if (academiquetV.hasError) {
                    $('#tirth-prev').trigger('click');
                }else {
                    if (tuteurV.hasError) {
                        $('#last-prev').trigger('click');
                    }
                }
            }
        }
    }
}

function validerInfosNaissance() {
    var $nom = $('#etudiant_inscris_etudiant_nom');
    var $prenom = $('#etudiant_inscris_etudiant_prenom');
    var $lieuDeNaissance = $('#etudiant_inscris_etudiant_lieuDeNaissance');
    var $sexe = $('#etudiant_inscris_etudiant_sexe');
    var $pays = $('#etudiant_inscris_etudiant_pays');
    var $region = $('#etudiant_inscris_etudiant_region');
    var $departement = $('#etudiant_inscris_etudiant_departement');
    var data = '';

    var hasError = false;

    if ($sexe.val().trim() == "") {
        $('#select2-etudiant_inscris_etudiant_sexe-container').removeClass(success).addClass(danger);
        hasError = true;
    }else {
        $('#select2-etudiant_inscris_etudiant_sexe-container').removeClass(danger).addClass(success);
    }

    if ($pays.val().trim() == "") {
        $('#select2-etudiant_inscris_etudiant_pays-container').removeClass(bgs).addClass(bgd);
        hasError = true;
    }else {
        $('#select2-etudiant_inscris_etudiant_pays-container').removeClass(bgd).addClass(bgs);
    }

    if ($departement.val().trim() == "") {
        $('#select2-etudiant_inscris_etudiant_departement-container').removeClass(bgs).addClass(bgd);
        hasError = true;
    }else {
        $('#select2-etudiant_inscris_etudiant_departement-container').removeClass(bgd).addClass(bgs);
    }

    if ($region.val().trim() == "") {
        $('#select2-etudiant_inscris_etudiant_region-container').removeClass(bgs).addClass(bgd);
        hasError = true;
    }else {
        $('#select2-etudiant_inscris_etudiant_region-container').removeClass(bgd).addClass(bgs);
    }

    if ($lieuDeNaissance.val().trim() == "" || $lieuDeNaissance.val().trim().length < 2 ) {
        $lieuDeNaissance.removeClass(bgs).addClass(bgd);
        hasError = true;
    }else {
        $lieuDeNaissance.removeClass(bgd).addClass(bgs);
    }

    if ($nom.val().trim() == "" || $nom.val().trim().length < 2 ) {
        $nom.removeClass(bgs).addClass(bgd);
        hasError = true;
    }else {
        $nom.removeClass(bgd).addClass(bgs);
    }

    if ($prenom.val().trim() != "" && $prenom.val().trim().length < 2 ) {
        $prenom.removeClass(bgs).addClass(bgd);
        hasError = true;
    }else {
        $prenom.removeClass(bgd).addClass(bgs);
    }

    if (!hasError) {
        var img_src = $('#previsualisation').prop('src');
        data = "<h3>Informations Personnelles</h3><div class='row'><div class='col-md-5'><img style='width:100%' src='"+img_src+"'></div>";
        data += "<div class='col-md-7'>";
        var sexe = $sexe.val() == 'M' ? "Masculin" : "feminin";
        data += '<p>Nom : '+$nom.val()+'</p>';
        data += '<p>Prenom : '+$prenom.val()+'</p>';
        data += '<p>Né(e) le <b>'+$('#etudiant_inscris_etudiant_dateDeNaissance_day').val()+'/'+$('#etudiant_inscris_etudiant_dateDeNaissance_month').val()+'/'+$('#etudiant_inscris_etudiant_dateDeNaissance_year').val();
        data += ' à <b>'+$lieuDeNaissance.val()+'</b></p>';
        data += '<p>De sexe <b>'+sexe+'</b></p>';
        var pays = $('#etudiant_inscris_etudiant_pays option:selected').text();
        var region = $('#etudiant_inscris_etudiant_region option:selected').text();
        var dept = $('#etudiant_inscris_etudiant_departement option:selected').text();
        data += '<p>Pays d\'origine est : <b>'+pays+'</b></p>';
        data += '<p>Région d\'origine est : <b>'+region+'</b></p>';
        data += '<p>Département d\'origine est : <b>'+dept+'</b></p>';
        data += "</div></div>";
    }

    return {
        'hasError': hasError,
        'data': data
    };
}

function validerInfosAcademiques() {
    var data = '';
    var hasError = false;
    var $diplomeMax = $('#etudiant_inscris_etudiant_diplomeAcademiqueMax');
    var $otherDiplomeMax = $('#etudiant_inscris_etudiant_autreDiplomeMax');
    var $ADM = $('#etudiant_inscris_etudiant_anneeObtentionDiplomeAcademiqueMax');
    var $diplomeEntrer = $('#etudiant_inscris_etudiant_diplomeDEntre');
    var $otherDiplomeEntrer = $('#etudiant_inscris_etudiant_autreDiplomeEntre');
    var $ADE = $('#etudiant_inscris_etudiant_anneeObtentionDiplomeEntre');
    var $othersFormations = $('#etudiant_inscris_etudiant_autreFormation');
    var $matricule = $('#etudiant_inscris_matricule');
    
    if ($diplomeMax.val().trim() == "") {
        hasError = true;
        $('#select2-etudiant_inscris_etudiant_diplomeAcademiqueMax-container').addClass(bgd).removeClass(bgs);
    }else {
        $('#select2-etudiant_inscris_etudiant_diplomeAcademiqueMax-container').removeClass(bgd).addClass(bgs);
    }

    if ($diplomeMax.val() == 0 && $otherDiplomeMax.val().trim() == ""){
        hasError = true;
        $otherDiplomeMax.addClass(bgd).removeClass(bgs);
    }else {
        $otherDiplomeMax.removeClass(bgd).addClass(bgs);
    }

    if ($diplomeEntrer.val().trim() == "") {
        hasError = true;
        $('#select2-etudiant_inscris_etudiant_diplomeDEntre-container').addClass(bgd).removeClass(bgs);
    }else {
        $('#select2-etudiant_inscris_etudiant_diplomeDEntre-container').removeClass(bgd).addClass(bgs);
    }

    if ($diplomeEntrer.val() == 0 && $otherDiplomeEntrer.val().trim().length < 3){
        hasError = true;
        $otherDiplomeEntrer.addClass(bgd).removeClass(bgs);
    }else {
        $otherDiplomeEntrer.removeClass(bgd).addClass(bgs);
    }

    if ($ADM.val().trim() == "" || $ADM.val().trim() < 1990) {
        hasError = true;
        $ADM.addClass(bgd).removeClass(bgs);
    }else{
        $ADM.addClass(bgs).removeClass(bgd);
    }

    if ($ADE.val().trim() == "" || $ADE.val().trim() < 1990) {
        hasError = true;
        $ADE.addClass(bgd).removeClass(bgs);
    }else{
        $ADE.addClass(bgs).removeClass(bgd);
    }
    if ($matricule.length) {
        if ($matricule.val().trim().length < 5) {
            hasError = true;
            $matricule.addClass(bgd).removeClass(bgs);
        }else {
            $matricule.addClass(bgs).removeClass(bgd);
        }
    }
    

    if (!hasError) {
        dm = $diplomeMax.val() == 0 ? $otherDiplomeMax.val() : $diplomeMax.val();
        de = $diplomeEntrer.val() == 0 ? $otherDiplomeEntrer.val() : $diplomeEntrer.val();
        data = "<div class='row'><div class='col-12'>";
        data += "<h3>Informations Académiques</h3>";
        data += "<p>Diplôme académique le plus élévé : <b>"+dm+"</b> ";
        data += "Année d'obtention : <b>"+$ADM.val()+"</b></p>";
        data += "<p>Diplôme d'entrer : <b>"+de+"</b> ";
        data += "Année d'obtention : <b>"+$ADE.val()+"</b></p>";
        data += "<h4>Autres formations suivies</h4>";
        data += "<p><i>"+$othersFormations.val()+"</i></p>";
        data += "<hr><h4>Inscription</h4>";
        data += "<p>Vous serez inscrit en <strong>"+$filiere+"</strong></p>";
        data += "<p>Option <strong>"+$specialite+"</strong></p>";
        data += "<p>Niveau : <strong>"+$level+"</strong></p>";
        data += "<p>Votre matricule est le <strong>"+$matricule.val()+"</strong></p>";
        data += "</div></div>";
    }

    return {
        'hasError': hasError,
        'data': data,
    }
}

function validerInfosContact() {
    var data = '';
    var hasError = false;
    var $address = $('#etudiant_inscris_etudiant_localisation');
    var $civilite = $('#etudiant_inscris_etudiant_civilite');
    var $situationMatrimoniale = $('#etudiant_inscris_etudiant_situationMatrimoniale');
    var $nombreDEnfants = $('#etudiant_inscris_etudiant_nombreDEnfants');
    var $telephone1 = $('#etudiant_inscris_etudiant_telephone1');
    var $telephone2 = $('#etudiant_inscris_etudiant_telephone2');
    var $adresseEmail = $('#etudiant_inscris_etudiant_adresseEmail');
    var $skills = $('#etudiant_inscris_etudiant_skills');

    if ($address.val().trim() == "" || $address.val().trim().length < 5){
        hasError = true;
        $address.addClass(bgd).removeClass(bgs);
    }else {
        $address.removeClass(bgd).addClass(bgs);
    }

    if ($civilite.val().trim() == ""){
        hasError = true;
        $('#select2-etudiant_inscris_etudiant_civilite-container').addClass(bgd).removeClass(bgs);
    }else {
        $('#select2-etudiant_inscris_etudiant_civilite-container').removeClass(bgd).addClass(bgs);
    }

    if ($nombreDEnfants.val().trim() == ""){
        hasError = true;
        $nombreDEnfants.addClass(bgd).removeClass(bgs);
    }else {
        $nombreDEnfants.removeClass(bgd).addClass(bgs);
    }

    if ($telephone1.val().trim() == "" || $telephone1.val().trim().length != 9){
        hasError = true;
        $telephone1.addClass(bgd).removeClass(bgs);
    }else {
        $telephone1.removeClass(bgd).addClass(bgs);
    }

    if ($telephone2.val().trim() != "" && $telephone1.val().trim().length != 9){
        hasError = true;
        $telephone2.addClass(bgd).removeClass(bgs);
    }else {
        $telephone2.removeClass(bgd).addClass(bgs);
    }

    if ($adresseEmail.val().trim() == ""){
        hasError = true;
        $adresseEmail.addClass(bgd).removeClass(bgs);
    }else {
        $adresseEmail.removeClass(bgd).addClass(bgs);
    }

    if ($situationMatrimoniale.val().trim() == ""){
        hasError = true;
        $('#select2-etudiant_inscris_etudiant_situationMatrimoniale-container').addClass(bgd).removeClass(bgs);
    }else {
        $('#select2-etudiant_inscris_etudiant_situationMatrimoniale-container').removeClass(bgd).addClass(bgs);
    }

    if (!hasError) {
        data = "<div class='row'><div class='col-12'>";
        data += "<h3>Contact</h3>";
        var sm = $('#etudiant_inscris_etudiant_situationMatrimoniale option:selected').text();
        data += "<p>Situation matrimoniale : <b>"+sm+"</b></p>";
        data += "<p>Adresse ou localisation : <b>"+$address.val()+"</b></p>";
        data += "<p>Numéros de téléphone : <b>"+$telephone1.val()+"</b> / <b>"+$telephone2.val()+"</b></p>";
        data += "<p>Adresse e-mail : <b>"+$adresseEmail.val()+"</b></p><hr>";
        data += "<h4>Centres d'interets</h4>";
        data += "<p>"+$('#etudiant_inscris_etudiant_skills').val()+"</p>";
        data += "</div></div>";
    }

    return {
        'hasError': hasError,
        'data': data,
    }
}

function validerInfosTuteur() {
    var data = '';
    var hasError = false;
    var $pere = $('#etudiant_inscris_etudiant_nomDuPere');
    var $telP = $('#etudiant_inscris_etudiant_numeroDeTelephoneDuPere');
    var $profPere = $('#etudiant_inscris_etudiant_professionDuPere');
    var $mere = $('#etudiant_inscris_etudiant_nomDeLaMere');
    var $telM = $('#etudiant_inscris_etudiant_numeroDeTelephoneDeLaMere');
    var $profMere = $('#etudiant_inscris_etudiant_professionDeLaMere');
    var $addressP = $('#etudiant_inscris_etudiant_adresseDesParents');
    var $nomTuteur = $('#etudiant_inscris_etudiant_personneAContacterEnCasDeProbleme');
    var $numUrgence = $('#etudiant_inscris_etudiant_numeroDUrgence');

    if ($pere.val().trim() != "" && $pere.val().trim().length < 2) {
        hasError = true;
        $pere.addClass(bgd).removeClass(bgs);
    }else{
        $pere.addClass(bgs).removeClass(bgd);
    }

    if ($telP.val().trim() != "" && $telP.val().trim().length != 9) {
        hasError = true;
        $telP.addClass(bgd).removeClass(bgs);
    }else{
        $telP.addClass(bgs).removeClass(bgd);
    
    }
    if ($telM.val().trim() != "" && $telM.val().trim().length != 9) {
        hasError = true;
        $telM.addClass(bgd).removeClass(bgs);
    }else{
        $telM.addClass(bgs).removeClass(bgd);
    }

    if ($profPere.val().trim() != "" && $profPere.val().trim().length < 2) {
        hasError = true;
        $profPere.addClass(bgd).removeClass(bgs);
    }else{
        $profPere.addClass(bgs).removeClass(bgd);
    }

    if ($mere.val().trim() == "" || $mere.val().trim().length < 2) {
        hasError = true;
        $mere.addClass(bgd).removeClass(bgs);
    }else{
        $mere.addClass(bgs).removeClass(bgd);
    }

    if ($profMere.val().trim() == "" || $profMere.val().trim().length < 2) {
        hasError = true;
        $profMere.addClass(bgd).removeClass(bgs);
    }else{
        $profMere.addClass(bgs).removeClass(bgd);
    }

    if ($addressP.val().trim() == "" || $addressP.val().trim().length < 3) {
        hasError = true;
        $addressP.addClass(bgd).removeClass(bgs);
    }else{
        $addressP.addClass(bgs).removeClass(bgd);
    }

    if ($nomTuteur.val().trim() == "" || $nomTuteur.val().trim().length < 2) {
        hasError = true;
        $nomTuteur.addClass(bgd).removeClass(bgs);
    }else{
        $nomTuteur.addClass(bgs).removeClass(bgd);
    }

    if ($numUrgence.val().trim() == "" || $numUrgence.val().trim().length != 9) {
        hasError = true;
        $numUrgence.addClass(bgd).removeClass(bgs);
    }else{
        $numUrgence.addClass(bgs).removeClass(bgd);
    }

    if (!hasError) {
        data = "<div class='row'><div class='col-12'>";
        data += "<h3>Parents / Tuteurs</h3>";
        data += "<p>Nom du père : <b>"+$pere.val()+"</b>; ";
        data += "profession : <b>"+$profPere.val()+"</b>; ";
        data += "numéro de téléphone : <b>"+$telP.val()+"</b></p> ";
        data += "<p>Nom de la mère : <b>"+$mere.val()+"</b>; ";
        data += "profession : <b>"+$mere.val()+"</b>; ";
        data += "numéro de téléphone : <b>"+$telM.val()+"</b></p> ";
        data += "<p>Adresse des parents : <b>"+$addressP.val()+"</b></p>";
        data += "<h4>En cas de problème contater en urgence</h4>";
        data += "<p>Monsieur / Madame <b><i>"+$nomTuteur.val()+"</i></b> Au numéro suivant <b><i>"+$numUrgence.val()+"</i></b></p>";
        data += "<br><br><div class='alert alert-success'><label><input required type='checkbox'> Je confirme que ces informations sont vraies</label></div>";
        data += "</div></div>";
    }

    return {
        'hasError': hasError,
        'data': data,
    }
}

$('#first-next, #second-prev, #second-next, #first-prev, #last-prev, #tirth-prev, #tirth-next, #confirmation-btn').addClass('no-loader');

$('#confirmation-btn').on('click', function(e){
    e.preventDefault();
    afficherInformations();
});

$('#first-next, #second-prev').on('click', function(e){
    var $v = validerInfosNaissance();
    if (!$v.hasError) {
        $('#vert-tabs-profile-tab').trigger('click');
        addActiveClass('#vert-contact');
    }else {
        $('#first-prev').trigger('click');
        showErrorAlert();
    }
    
});
$('#second-next, #tirth-prev').on('click', function(e){
    var $v = validerInfosContact();
    if (!$v.hasError) {
        $('#vert-tabs-messages-tab').trigger('click');
        addActiveClass('#vert-academique');
    }else {
        $('#first-next').trigger('click');
        showErrorAlert();
    }
    
});
$('#tirth-next').on('click', function(e){
    var $v = validerInfosAcademiques();
    if (!$v.hasError) {
        $('#vert-tabs-settings-tab').trigger('click');
        addActiveClass('#vert-tuteur');
    }else {
        $('#second-next').trigger('click');
        showErrorAlert();
    }
    
});
$('#first-prev').on('click', function(e){
    $('#vert-tabs-home-tab').trigger('click');
    addActiveClass('#vert-nais');
});
$('#last-prev').on('click', function(e) {
    $('#tirth-next').trigger('click');
});
$('#etudiant_inscris_etudiant_image').change(function(){
    var oFReader = new FileReader();
    oFReader.readAsDataURL(document.getElementById("etudiant_inscris_etudiant_image").files[0]);
    oFReader.onload = function(oFREvent) {
        document.getElementById("previsualisation").src = oFREvent.target.result;
    };
});
$('#etudiant_inscris_etudiant_region').on('change', function(){
    var value = $(this).val();
    if (value != '') {
        $.ajax({
            url: fetchDepartementUrl+'/'+value,
            success: function(response) {
                $('#etudiant_inscris_etudiant_departement').html(response);
                showLoader(false);
            },
            beforeSend: function(){
                showLoader();
            },
            error: function() {
                showLoader(false);
                alert("Une erreur est survenue ! verifiez votre connexion internet svp.")
            }
        });
    }else {
        $('#etudiant_inscris_etudiant_departement').html('<option value="">'+selectRegionText+'</option>')
    }
});

$('#etudiant_inscris_etudiant_diplomeAcademiqueMax').on('change', function(){
    if ($(this).val() == 0)
    {
        $('#etudiant_inscris_etudiant_autreDiplomeMax').attr('required', true);
        $('#etudiant_inscris_etudiant_autreDiplomeMax').attr('type', 'text');
    }else{
        $('#etudiant_inscris_etudiant_autreDiplomeMax').attr('required', false);
        $('#etudiant_inscris_etudiant_autreDiplomeMax').attr('type', 'hidden');
    }
});
$('#etudiant_inscris_etudiant_diplomeDEntre').on('change', function(){
    if ($(this).val() == 0)
    {
        $('#etudiant_inscris_etudiant_autreDiplomeEntre').attr('required', true);
        $('#etudiant_inscris_etudiant_autreDiplomeEntre').attr('type', 'text');
    }else{
        $('#etudiant_inscris_etudiant_autreDiplomeEntre').attr('required', false);
        $('#etudiant_inscris_etudiant_autreDiplomeEntre').attr('type', 'hidden');
    }
});

if($('#preinscription').length){
    $('form[name=etudiant_inscris]').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            type: "POST",
            url: $(this).prop('action'),
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "JSON",
            success: function(response) {
                showLoader(false);
                setToastTimer(15000);
                showErrorAlert(response.msg, 'success');
                $('#downlod-fiche-container').html('');
                if (!response.formHasError) {
                    $('#downlod-fiche-container').html("<div class='alert alert-info'>"+response.downloadUrl+"</div>");
                }
            },
            beforeSend: function() {
                showLoader();
            },
            error: function() {
                showLoader(false);
                setToastTimer(15000);
                showErrorAlert("Un probleme est survenu! Assurer vous que votre numero de telephone, votre matricule ou votre adresse email ne sont pas encore utilisés. Verifier votre connxion internet.");
            }
        })
    });
}