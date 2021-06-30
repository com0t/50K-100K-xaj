<?php
/**
 * This class extends JupiterX_WXR_Importer.
 *
 * @package JupiterX\Framework\Control_Panel\Importer
 *
 * @since 1.1.0
 */

/**
 * JupiterX Importer.
 *
 * @since 1.1.0
 * @package JupiterX\Framework\Control_Panel\Importer
 */
class JupiterX_Importer extends JupiterX_WXR_Importer {

	/**
	 * Term meta.
	 *
	 * @var array
	 */
	protected $jupiterx_meta = [];

	/**
	 * Import pages only.
	 *
	 * @var boolean
	 */
	protected $partial_import;

	/**
	 * Constructor.
	 *
	 * @param array $options The JupiterX_WXR_Importer options.
	 */
	public function __construct( $options = [], $partial_import = false ) {
		parent::__construct( $options, $partial_import );

		add_action( 'wp_import_insert_term', [ $this, 'jupiterx_process_term_meta' ] );
		add_action( 'wxr_importer.pre_process.term', [ $this, 'jupiterx_core_insert_term_id' ] );
	}

	/**
	 * Parse term node.
	 *
	 * It's a copy from the parent class. There's no way to access `$node` variable through
	 * actions so it's necessary to override it.
	 *
	 * The addition is `_jupiterx_parse_term_meta_node` method call.
	 *
	 * @since 1.1.0
	 */
	protected function parse_term_node( $node, $type = 'term' ) {
		$data = array();
		$meta = array();

		$tag_name = array(
			'id'          => 'wp:term_id',
			'taxonomy'    => 'wp:term_taxonomy',
			'slug'        => 'wp:term_slug',
			'parent'      => 'wp:term_parent',
			'name'        => 'wp:term_name',
			'description' => 'wp:term_description',
		);
		$taxonomy = null;

		// Special casing!
		switch ( $type ) {
			case 'category':
				$tag_name['slug']        = 'wp:category_nicename';
				$tag_name['parent']      = 'wp:category_parent';
				$tag_name['name']        = 'wp:cat_name';
				$tag_name['description'] = 'wp:category_description';
				$tag_name['taxonomy']    = null;

				$data['taxonomy'] = 'category';
				break;

			case 'tag':
				$tag_name['slug']        = 'wp:tag_slug';
				$tag_name['parent']      = null;
				$tag_name['name']        = 'wp:tag_name';
				$tag_name['description'] = 'wp:tag_description';
				$tag_name['taxonomy']    = null;

				$data['taxonomy'] = 'post_tag';
				break;
		}

		foreach ( $node->childNodes as $child ) {
			// We only care about child elements
			if ( $child->nodeType !== XML_ELEMENT_NODE ) {
				continue;
			}

			$key = array_search( $child->tagName, $tag_name );
			if ( $key ) {
				$data[ $key ] = $child->textContent;
			}
		}

		$this->_jupiterx_parse_term_meta_node( $node->childNodes );

		if ( empty( $data['taxonomy'] ) ) {
			return null;
		}

		// Compatibility with WXR 1.0
		if ( $data['taxonomy'] === 'tag' ) {
			$data['taxonomy'] = 'post_tag';
		}

		return compact( 'data', 'meta' );
	}

	/**
	 * Parse term meta node.
	 *
	 * @since 1.1.0
	 */
	private function _jupiterx_parse_term_meta_node( $childNodes ) {
		$this->jupiterx_meta = [];

		foreach ( $childNodes as $child ) {

			if ( $child->nodeType !== XML_ELEMENT_NODE ) {
				continue;
			}

			if ( 'wp:termmeta' !== $child->tagName ) {
				continue;
			}

			$meta_node = $this->parse_meta_node( $child );

			$this->jupiterx_meta[ $meta_node['key'] ] = $meta_node['value'];
		}
	}

	/**
	 * Process term meta.
	 *
	 * @since 1.1.0
	 */
	public function jupiterx_process_term_meta( $term_id ) {

		if ( empty( $this->jupiterx_meta ) ) {
			return;
		}

		foreach( $this->jupiterx_meta as $term_key => $term_val ) {
			update_term_meta( $term_id, $term_key, $term_val );
		}
	}

	/**
	 * Add term_id to term_data to prevent different IDs.
	 *
	 * @since 1.13.0
	 */
	public function jupiterx_core_insert_term_id( $data ) {
		add_filter( 'wp_insert_term_data', function ( $term_data ) use ( $data ) {
			if ( ! empty( $data['id'] ) ) {
				$term_data['term_id'] = $data['id'];
			}

			return $term_data;
		} );

		return $data;
	}
}
