<?php
// check for rights
if(!current_user_can('edit_posts')) die;
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php _e('Image Gallery', 'media-gallery') ?></title>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<script language="javascript" type="text/javascript" src="<?php echo includes_url(); ?>js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo includes_url(); ?>js/tinymce/utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo includes_url(); ?>js/tinymce/utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo MEDIA_GALLERY_URL; ?>tinymce/tinymce.js"></script>
	<base target="_self" />
	
</head>
<body id="link" onload="tinyMCEPopup.executeOnLoad('init();');document.body.style.display='';" style="display: none">
<form name="ImageGalleryTinyMCE" action="#">
	<div class="tabs"></div>
	
	<div class="panel_wrapper" style="height: 150px;">
		
		<!-- gallery panel -->
		<div id="gallery_panel" class="panel current">
		<table style="border: 0;">
		<tr>
			<td><label for="gallery"><?php _e("Gallery", 'media-gallery'); ?></label></td>
			<td>			
				<select size='1' name='gallery' id='gallery'>
					<?php $galleries = get_terms("gallery", 'orderby=name&hide_empty=0'); ?>
					<?php if (!empty($galleries)) : ?>
					<?php foreach ( $galleries as $gallery ) : ?>
					<option value="<?php echo $gallery->term_id ?>"><?php echo htmlspecialchars(stripslashes($gallery->name)) ?></option>
					<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="perpage"><?php _e('Images per page', 'media-gallery') ?></label></td>
			<td><input type="text" name="perpage" id="perpage" size="4" /></td>
		</tr>
		<tr>
			<td><label for="ncol"><?php _e('Number of columns', 'media-gallery') ?></label></td>
			<td><input type="text" name="ncol" id="ncol" size="4" /></td>
		</tr>
		<tr>
			<td><label for="crop"><?php _e('Crop Images', 'media-gallery') ?></label></td>
			<td><input type="checkbox" name="crop" id="crop" value="1" /></td>
		</tr>
		</table>
		<?php _e( 'Activating cropping will resize & crop images to the smallest image widths and heights', 'media-gallery') ?>
		</div>
			
	</div>
	
	<br style="clear: both;" />
	<div class="mceActionPanel" style="margin-top: 0.5em;">
		<div style="float: left">
			<input type="button" id="cancel" name="cancel" value="<?php _e("Cancel", 'media-gallery'); ?>" onclick="tinyMCEPopup.close();" />
		</div>

		<div style="float: right">
			<input type="submit" id="insert" name="insert" value="<?php _e("Insert", 'media-gallery'); ?>" onclick="MediaGalleryInsertLink();" />
		</div>
	</div>
</form>
</body>
</html>