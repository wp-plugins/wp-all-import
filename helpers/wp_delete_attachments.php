<?php
/**
 * Delete attachments linked to a specified post
 * @param int $parent_id Parent id of post to delete attachments for
 */
function wp_delete_attachments($parent_id) {
	foreach (get_posts(array('post_parent' => $parent_id, 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null)) as $attach) {
		wp_delete_attachment($attach->ID, true);
	}
}