function init() {
	tinyMCEPopup.resizeToInnerSize();
}

function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}

function MediaGalleryInsertLink() {
	
	var tagtext;
	
	var gallery = document.getElementById('gallery').value;
	var perpage = document.getElementById('perpage').value;
	var ncol = document.getElementById('ncol').value;
	var crop = getCheckedValue(document.getElementById('crop'));

	if ( crop == 1 )
		crop = " crop='true'";
	else
		crop = "";
	
	tagtext = "[media-gallery gallery=" + gallery + " perpage=" + perpage + " ncol=" + ncol + crop + "]";

	if(window.tinyMCE) {
		/* get the TinyMCE version to account for API diffs */
		var tmce_ver=window.tinyMCE.majorVersion;
		
		if (tmce_ver>="4") {
			window.tinyMCE.execCommand('mceInsertContent', false, tagtext);
		} else {
			window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
		}
		//Peforms a clean up of the current editor HTML. 
		//tinyMCEPopup.editor.execCommand('mceCleanup');
		//Repaints the editor. Sometimes the browser has graphic glitches. 
		tinyMCEPopup.editor.execCommand('mceRepaint');
		tinyMCEPopup.close();
	}
	return;
}