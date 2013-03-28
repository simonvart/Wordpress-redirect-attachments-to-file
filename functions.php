<?php

// TOOL
// GET ATTACHMENT ID FROM GUID
// Tx to Rarst http://wordpress.stackexchange.com/questions/6645/turn-a-url-into-an-attachment-post-id
function get_attachment_id( $url ) {

    $dir = wp_upload_dir();
    $dir = trailingslashit($dir['baseurl']);

    if( false === strpos( $url, $dir ) )
        return false;

    $file = basename($url);

    $query = array(
        'post_type' => 'attachment',
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'value' => $file,
                'compare' => 'LIKE',
            )
        )
    );

    $query['meta_query'][0]['key'] = '_wp_attached_file';
    $ids = get_posts( $query );

    foreach( $ids as $id )
        if( $url == array_shift( wp_get_attachment_image_src($id, 'full') ) )
            return $id;

    $query['meta_query'][0]['key'] = '_wp_attachment_metadata';
    $ids = get_posts( $query );

    foreach( $ids as $id ) {

        $meta = wp_get_attachment_metadata($id);

        foreach( $meta['sizes'] as $size => $values )
            if( $values['file'] == $file && $url == array_shift( wp_get_attachment_image_src($id, $size) ) ) {

                return $id;
            }
    }

    return false;
}

// GET INNER HTML OF A NODE
function DOMinnerHTML($element) 
{ 
    $innerHTML = ""; 
    $children = $element->childNodes; 
    foreach ($children as $child) 
    { 
        $tmp_dom = new DOMDocument(); 
        $tmp_dom->appendChild($tmp_dom->importNode($child, true)); 
        $innerHTML.=trim($tmp_dom->saveHTML()); 
    } 
    return $innerHTML; 
} 

function remove_bad_img_links($content) {   
    $dom = new DOMDocument();
    // THIS IS HACK TO LOAD STRING WITH CORRECT ENCODING
    // JUST OUTPUT <--?xml encoding="UTF-8"--> IN HTML SO NO HARM
    $dom->loadHTML( '<?xml encoding="UTF-8">' .  $content );

    // GET ALL <a> NODE
    foreach ( $dom->getElementsByTagName('a') as $node ) {
            // GET HREF 
        $link_href = $node->getAttribute( 'href' );
            // USE INNER OF THIS <a> NODE AS NEW DOC TO EXTRACT IMG
        $dom_node = new DOMDocument();
        $inner = DOMinnerHTML($node);
        $dom_node->loadHTML($inner);
            // EXTRACT IMG AND GET SRC OF IT
            // ASSUMING THERE IS ONLY ONE IMAGE ...
        foreach ( $dom_node->getElementsByTagName('img') as $img_node ) {
            $img_node_link = $img_node->getAttribute( 'src' );
        }
            // CHECK IF THE WORD attachment IS IN HREF
        preg_match('/attachment/', $link_href, $matches);
            // IF SO...
        if ( $matches ) {
                    // GET ID OF THE IMAGE VIA CUSTOM FUNCTION
            $img_id = get_attachment_id( $img_node_link );
                    // GET ARRAY OF THE IMAGE VIA BUILTIN FUNCTION
            $img_array = wp_get_attachment_image_src( $img_id, 'large' );
                    // REPLACE HREF WITH NEW SOURCE
            $node->setAttribute('href', $img_array[0] );
        }
    // RETURN MODIFIED DOM
    if ( $matches ) $content = $dom->saveHTML();
    }
    // RETURN CONTENT
    return $content;
}
// APPLY FILTER
add_filter( 'the_content', 'remove_bad_img_links' );
