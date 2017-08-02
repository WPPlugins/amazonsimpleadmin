��$( document ).ready(function() {
    asa_load_collection_shortcode();
});

function asa_checkAll() {
    var form = document.getElementById("collection-filter");
    for (i = 0, n = form.elements.length; i < n; i++) {
        if(form.elements[i].type == "checkbox" && !(form.elements[i].getAttribute('onclick',2))) {
            if(form.elements[i].checked == true)
                form.elements[i].checked = false;
            else
                form.elements[i].checked = true;
        }
    }
}

function asa_deleteCollectionItems( message ) {
    if (confirm(message)) {
        return true;
    }
    
    return false;
}

function asa_deleteCollection () {
    if (confirm('Do you realy want to delete this collection?')) {
        return true;
    }
    
    return false;
}

function asa_load_collection_shortcode () {
    var selected = $( "#select_manage_collection option:selected" ).text();
    if (selected != '') {
        $('#asa_collection_shortcode span').text(selected);
        $('#asa_collection_shortcode').show();
    } else {
        $('#asa_collection_shortcode').hide();
    }
}


function asa_set_latest (id, message) {
    
    if (confirm(message)) {
        return true;
    }
    
    return false;
}��