<?php
/**
 * oEmbed Manager analytics
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Oemm\Plugin\Feature;

use Oemm\System\Blog;
use Oemm\System\Cache;
use Oemm\System\Date;
use Oemm\System\Conversion;
use Oemm\System\Role;
use Oemm\System\Logger;
use Oemm\System\L10n;
use Oemm\System\Http;
use Oemm\System\Favicon;
use Oemm\System\Timezone;
use Oemm\System\UUID;
use Feather;
use Flagiconcss;


/**
 * Define the analytics functionality.
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Analytics {

	/**
	 * The domain name.
	 *
	 * @since  1.0.0
	 * @var    string    $domain    The domain name.
	 */
	public $domain = '';

	/**
	 * The subdomain name.
	 *
	 * @since  1.0.0
	 * @var    string    $subdomain    The subdomain name.
	 */
	public $subdomain = '';

	/**
	 * The dashboard type.
	 *
	 * @since  1.0.0
	 * @var    string    $title    The dashboard type.
	 */
	public $type = '';

	/**
	 * The dashboard extra.
	 *
	 * @since  1.0.0
	 * @var    string    $extra    The dashboard extra.
	 */
	public $extra = '';

	/**
	 * The dashboard context.
	 *
	 * @since  1.0.0
	 * @var    string    $context    The dashboard context.
	 */
	private $context = '';

	/**
	 * The queried ID.
	 *
	 * @since  1.0.0
	 * @var    string    $id    The queried ID.
	 */
	private $id = '';

	/**
	 * The queried site.
	 *
	 * @since  1.0.0
	 * @var    string    $site    The queried site.
	 */
	public $site = 'all';

	/**
	 * The start date.
	 *
	 * @since  1.0.0
	 * @var    string    $start    The start date.
	 */
	private $start = '';

	/**
	 * The end date.
	 *
	 * @since  1.0.0
	 * @var    string    $end    The end date.
	 */
	private $end = '';

	/**
	 * The period duration in seconds.
	 *
	 * @since  1.0.0
	 * @var    integer    $duration    The period duration in seconds.
	 */
	private $duration = 0;

	/**
	 * The timezone.
	 *
	 * @since  1.0.0
	 * @var    string    $timezone    The timezone.
	 */
	private $timezone = 'UTC';

	/**
	 * The main query filter.
	 *
	 * @since  1.0.0
	 * @var    array    $filter    The main query filter.
	 */
	private $filter = [];

	/**
	 * The query filter fro the previous range.
	 *
	 * @since  1.0.0
	 * @var    array    $previous    The query filter fro the previous range.
	 */
	private $previous = [];

	/**
	 * Is the start date today's date.
	 *
	 * @since  1.0.0
	 * @var    boolean    $today    Is the start date today's date.
	 */
	private $is_today = false;

	/**
	 * Has the dataset inbound context.
	 *
	 * @since  1.0.0
	 * @var    boolean    $has_inbound    Has the dataset inbound context.
	 */
	private $has_inbound = false;

	/**
	 * Has the dataset inbound context.
	 *
	 * @since  1.0.0
	 * @var    boolean    $has_outbound    Has the dataset inbound context.
	 */
	private $has_outbound = false;

	/**
	 * Is the inbound context in query.
	 *
	 * @since  1.0.0
	 * @var    boolean    $has_inbound    Is the inbound context in query.
	 */
	private $is_inbound = false;

	/**
	 * Is the outbound context in query.
	 *
	 * @since  1.0.0
	 * @var    boolean    $has_outbound    Is the outbound context in query.
	 */
	private $is_outbound = false;

	/**
	 * Colors for graphs.
	 *
	 * @since  1.0.0
	 * @var    array    $colors    The colors array.
	 */
	private $colors = [ '#73879C', '#3398DB', '#9B59B6', '#b2c326', '#BDC3C6' ];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $domain  The domain name, if disambiguation is needed.
	 * @param   string  $type    The type of analytics ( summary, domain, authority, endpoint, country).
	 * @param   string  $context The context of analytics (both, inbound, outbound).
	 * @param   string  $site    The site to analyze (all or ID).
	 * @param   string  $start   The start date.
	 * @param   string  $end     The end date.
	 * @param   string  $id      The queried ID.
	 * @param   boolean $reload  Is it a reload of an already displayed analytics.
	 * @param   string  $extra   The extra view to render.
	 * @since    1.0.0
	 */
	public function __construct( $domain, $type, $context, $site, $start, $end, $id, $reload, $extra ) {
		$this->id      = $id;
		$this->extra   = $extra;
		$this->context = $context;
		if ( Role::LOCAL_ADMIN === Role::admin_type() ) {
			$site = get_current_blog_id();
		}
		$this->site = $site;
		if ( 'all' !== $site ) {
			$this->filter[]   = "site='" . $site . "'";
			$this->previous[] = "site='" . $site . "'";
		}
		if ( '' !== $domain ) {
			$this->domain     = $domain;
			$this->filter[]   = "id='" . $domain . "'";
			$this->previous[] = "id='" . $domain . "'";
		}
		if ( $start === $end ) {
			$this->filter[] = "timestamp='" . $start . "'";
		} else {
			$this->filter[] = "timestamp>='" . $start . "' and timestamp<='" . $end . "'";
		}
		$this->start = $start;
		$this->end   = $end;
		$this->type  = $type;
		if ( '' !== $id ) {
			switch ( $type ) {
				case 'domain':
				case 'authorities':
					$this->filter[]   = "id='" . $id . "'";
					$this->previous[] = "id='" . $id . "'";
					break;
				case 'authority':
				case 'endpoints':
					$this->filter[]   = "authority='" . $id . "'";
					$this->previous[] = "authority='" . $id . "'";
					//$this->subdomain  = Schema::get_authority( $this->filter );
					break;
				case 'endpoint':
					$this->filter[]   = "endpoint='" . $id . "'";
					$this->previous[] = "endpoint='" . $id . "'";
					//$this->subdomain  = Schema::get_authority( $this->filter );
					break;
				case 'country':
					$this->filter[]   = "country='" . strtoupper( $id ) . "'";
					$this->previous[] = "country='" . strtoupper( $id ) . "'";
					break;
				default:
					$this->type = 'summary';
			}
		}
		if ( '' !== $domain && 'domain' !== $type && 'authorities' !== $type ) {
			$this->domain     = $domain;
			$this->filter[]   = "id='" . $domain . "'";
			$this->previous[] = "id='" . $domain . "'";
		}
		$this->timezone     = Timezone::network_get();
		$datetime           = new \DateTime( 'now', $this->timezone );
		$this->is_today     = ( $this->start === $datetime->format( 'Y-m-d' ) || $this->end === $datetime->format( 'Y-m-d' ) );
		//$bounds             = Schema::get_distinct_context( $this->filter, ! $this->is_today );
		$this->has_inbound  = ( in_array( 'inbound', $bounds, true ) );
		$this->has_outbound = ( in_array( 'outbound', $bounds, true ) );
		$this->is_inbound   = ( 'inbound' === $context || 'both' === $context );
		$this->is_outbound  = ( 'outbound' === $context || 'both' === $context );
		if ( 'inbound' === $context && ! $this->has_inbound ) {
			$this->is_inbound  = false;
			$this->is_outbound = true;
		}
		if ( 'outbound' === $context && ! $this->has_outbound ) {
			$this->is_inbound  = true;
			$this->is_outbound = false;
		}
		if ( $this->is_inbound xor $this->is_outbound ) {
			$context = 'outbound';
			if ( $this->is_inbound ) {
				$context = 'inbound';
			}
			$this->filter[]   = "context='" . $context . "'";
			$this->previous[] = "context='" . $context . "'";
		}
		$start = new \DateTime( $this->start, $this->timezone );
		$end   = new \DateTime( $this->end, $this->timezone );
		$start->sub( new \DateInterval( 'P1D' ) );
		$end->sub( new \DateInterval( 'P1D' ) );
		$delta = $start->diff( $end, true );
		if ( $delta ) {
			$start->sub( $delta );
			$end->sub( $delta );
		}
		$this->duration = $delta->days + 1;
		if ( $start === $end ) {
			$this->previous[] = "timestamp='" . $start->format( 'Y-m-d' ) . "'";
		} else {
			$this->previous[] = "timestamp>='" . $start->format( 'Y-m-d' ) . "' and timestamp<='" . $end->format( 'Y-m-d' ) . "'";
		}
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $query   The query type.
	 * @param   mixed  $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query( $query, $queried ) {
		switch ( $query ) {
			case 'main-chart':
				return $this->query_chart();
			case 'map':
				return $this->query_map();
			case 'kpi':
				return $this->query_kpi( $queried );
			case 'top-domains':
				return $this->query_top( 'domains', (int) $queried );
			case 'top-authorities':
				return $this->query_top( 'authorities', (int) $queried );
			case 'top-endpoints':
				return $this->query_top( 'endpoints', (int) $queried );
			case 'sites':
				return $this->query_list( 'sites' );
			case 'domains':
				return $this->query_list( 'domains' );
			case 'authorities':
				return $this->query_list( 'authorities' );
			case 'endpoints':
				return $this->query_list( 'endpoints' );
			case 'codes':
				return $this->query_list( 'codes' );
			case 'schemes':
				return $this->query_list( 'schemes' );
			case 'methods':
				return $this->query_list( 'methods' );
			case 'countries':
				return $this->query_list( 'countries' );
			case 'code':
				return $this->query_pie( 'code', (int) $queried );
			case 'security':
				return $this->query_pie( 'security', (int) $queried );
			case 'method':
				return $this->query_pie( 'method', (int) $queried );
		}
		return [];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of pie.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_pie( $type, $limit ) {
		$extra_field = '';
		$extra       = [];
		$not         = false;
		$uuid        = UUID::generate_unique_id( 5 );
		switch ( $type ) {
			case 'code':
				$group       = 'code';
				$follow      = 'authority';
				$extra_field = 'code';
				$extra       = [ 0 ];
				$not         = true;
				break;
			case 'security':
				$group       = 'scheme';
				$follow      = 'endpoint';
				$extra_field = 'scheme';
				$extra       = [ 'http', 'https' ];
				$not         = false;
				break;
			case 'method':
				$group  = 'verb';
				$follow = 'domain';
				break;

		}
		$data  = Schema::get_grouped_list( $group, [], $this->filter, ! $this->is_today, $extra_field, $extra, $not, 'ORDER BY sum_hit DESC' );
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$result = '';
		$cpt    = 0;
		$labels = [];
		$series = [];
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.1 > $percent ) {
				$percent = 0.1;
			}
			$meta = strtoupper( $data[ $cpt ][ $group ] );
			if ( 'code' === $type ) {
				$meta = $data[ $cpt ][ $group ] . ' ' . Http::$http_status_codes[ (int) $data[ $cpt ][ $group ] ];
			}
			$labels[] = strtoupper( $data[ $cpt ][ $group ] );
			$series[] = [
				'meta'  => $meta,
				'value' => (float) $percent,
			];
			++$cpt;
		}
		if ( 0 < $other ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $other / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.1 > $percent ) {
				$percent = 0.1;
			}
			$labels[] = esc_html__( 'Other', 'oembed-manager' );
			$series[] = [
				'meta'  => esc_html__( 'Other', 'oembed-manager' ),
				'value' => (float) $percent,
			];
		}
		$result  = '<div class="oemm-pie-box">';
		$result .= '<div class="oemm-pie-graph">';
		$result .= '<div class="oemm-pie-graph-handler" id="oemm-pie-' . $group . '"></div>';
		$result .= '</div>';
		$result .= '<div class="oemm-pie-legend">';
		foreach ( $labels as $key => $label ) {
			$icon    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'square', $this->colors[ $key ], $this->colors[ $key ] ) . '" />';
			$result .= '<div class="oemm-pie-legend-item">' . $icon . '&nbsp;&nbsp;' . $label . '</div>';
		}
		$result .= '';
		$result .= '</div>';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var data' . $uuid . ' = ' . wp_json_encode(
			[
				'labels' => $labels,
				'series' => $series,
			]
		) . ';';
		$result .= ' var tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: true, appendToBody: true});';
		$result .= ' var option' . $uuid . ' = {width: 120, height: 120, showLabel: false, donut: true, donutWidth: "40%", startAngle: 270, plugins: [tooltip' . $uuid . ']};';
		$result .= ' new Chartist.Pie("#oemm-pie-' . $group . '", data' . $uuid . ', option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		return [ 'oemm-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of top.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_top( $type, $limit ) {
		switch ( $type ) {
			case 'authorities':
				$group  = 'authority';
				$follow = 'authority';
				break;
			case 'endpoints':
				$group  = 'endpoint';
				$follow = 'endpoint';
				break;
			default:
				$group  = 'id';
				$follow = 'domain';
				break;

		}
		$data  = Schema::get_grouped_list( $group, [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$result = '';
		$cpt    = 0;
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			$url = $this->get_url(
				[],
				[
					'type'   => $follow,
					'id'     => $data[ $cpt ][ $group ],
					'domain' => $data[ $cpt ]['id'],
				]
			);
			if ( 0.5 > $percent ) {
				$percent = 0.5;
			}
			$result .= '<div class="oemm-top-line">';
			$result .= '<div class="oemm-top-line-title">';
			$result .= '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $data[ $cpt ]['id'] ) . '" />&nbsp;&nbsp;<span class="oemm-top-line-title-text"><a href="' . esc_url( $url ) . '">' . $data[ $cpt ][ $group ] . '</a></span>';
			$result .= '</div>';
			$result .= '<div class="oemm-top-line-content">';
			$result .= '<div class="oemm-bar-graph"><div class="oemm-bar-graph-value" style="width:' . $percent . '%"></div></div>';
			$result .= '<div class="oemm-bar-detail">' . Conversion::number_shorten( $data[ $cpt ]['sum_hit'], 2, false, '&nbsp;' ) . '</div>';
			$result .= '</div>';
			$result .= '</div>';
			++$cpt;
		}
		if ( 0 < $total ) {
			$percent = round( 100 * $other / $total, 1 );
		} else {
			$percent = 100;
		}
		$result .= '<div class="oemm-top-line oemm-minor-data">';
		$result .= '<div class="oemm-top-line-title">';
		$result .= '<span class="oemm-top-line-title-text">' . esc_html__( 'Other', 'oembed-manager' ) . '</span>';
		$result .= '</div>';
		$result .= '<div class="oemm-top-line-content">';
		$result .= '<div class="oemm-bar-graph"><div class="oemm-bar-graph-value" style="width:' . $percent . '%"></div></div>';
		$result .= '<div class="oemm-bar-detail">' . Conversion::number_shorten( $other, 2, false, '&nbsp;' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return [ 'oemm-top-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $type    The type of list.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_list( $type ) {
		$follow     = '';
		$has_detail = false;
		$detail     = '';
		switch ( $type ) {
			case 'domains':
				$group      = 'id';
				$follow     = 'domain';
				$has_detail = true;
				break;
			case 'authorities':
				$group      = 'authority';
				$follow     = 'authority';
				$has_detail = true;
				break;
			case 'endpoints':
				$group  = 'endpoint';
				$follow = 'endpoint';
				break;
			case 'codes':
				$group = 'code';
				break;
			case 'schemes':
				$group = 'scheme';
				break;
			case 'methods':
				$group = 'verb';
				break;
			case 'countries':
				$group = 'country';
				break;
			case 'sites':
				$group  = 'site';
				break;
		}
		$data         = Schema::get_grouped_list( $group, [ 'authority', 'endpoint' ], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$detail_name  = esc_html__( 'Details', 'oembed-manager' );
		$calls_name   = esc_html__( 'Calls', 'oembed-manager' );
		$data_name    = esc_html__( 'Data Volume', 'oembed-manager' );
		$latency_name = esc_html__( 'Latency', 'oembed-manager' );
		$result       = '<table class="oemm-table">';
		$result      .= '<tr>';
		$result      .= '<th>&nbsp;</th>';
		if ( $has_detail ) {
			$result .= '<th>' . $detail_name . '</th>';
		}
		$result   .= '<th>' . $calls_name . '</th>';
		$result   .= '<th>' . $data_name . '</th>';
		$result   .= '<th>' . $latency_name . '</th>';
		$result   .= '</tr>';
		$other     = false;
		$other_str = '';
		foreach ( $data as $key => $row ) {
			$url         = $this->get_url(
				[],
				[
					'type'   => $follow,
					'id'     => $row[ $group ],
					'domain' => $row['id'],
				]
			);
			$name        = $row[ $group ];
			$other       = ( 'countries' === $type && ( empty( $name ) || 2 !== strlen( $name ) ) );
			$authorities = sprintf( esc_html( _n( '%d subdomain', '%d subdomains', $row['cnt_authority'], 'oembed-manager' ) ), $row['cnt_authority'] );
			$endpoints   = sprintf( esc_html( _n( '%d endpoint', '%d endpoints', $row['cnt_endpoint'], 'oembed-manager' ) ), $row['cnt_endpoint'] );
			switch ( $type ) {
				case 'sites':
					if ( 0 === (int) $row['sum_hit'] ) {
						break;
					}
					if ( 'summary' === $this->type ) {
						$url = $this->get_url(
							[],
							[
								'site' => $row['site'],
							]
						);
					} else {
						$url = $this->get_url(
							[],
							[
								'site'   => $row['site'],
								'domain' => $row['id'],
							]
						);
					}
					$site = Blog::get_blog_url( $row['site'] );
					$name = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $site ) . '" />&nbsp;&nbsp;<span class="oemm-table-text"><a href="' . esc_url( $url ) . '">' . $site . '</a></span>';
					break;
				case 'domains':
					$detail = $authorities . ' - ' . $endpoints;
					$name   = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="oemm-table-text"><a href="' . esc_url( $url ) . '">' . $name . '</a></span>';
					break;
				case 'authorities':
					$detail = $endpoints;
					$name   = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="oemm-table-text"><a href="' . esc_url( $url ) . '">' . $name . '</a></span>';
					break;
				case 'endpoints':
					$name = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="oemm-table-text"><a href="' . esc_url( $url ) . '">' . $name . '</a></span>';
					break;
				case 'codes':
					if ( '0' === $name ) {
						$name = '000';
					}
					$code = (int) $name;
					if ( 100 > $code ) {
						$http = '0xx';
					} elseif ( 200 > $code ) {
						$http = '1xx';
					} elseif ( 300 > $code ) {
						$http = '2xx';
					} elseif ( 400 > $code ) {
						$http = '3xx';
					} elseif ( 500 > $code ) {
						$http = '4xx';
					} elseif ( 600 > $code ) {
						$http = '5xx';
					} else {
						$http = 'nxx';
					}
					$name  = '<span class="oemm-http oemm-http-' . $http . '">' . $name . '</span>&nbsp;&nbsp;<span class="oemm-table-text">' . Http::$http_status_codes[ $code ] . '</span>';
					$group = 'code';
					break;
				case 'schemes':
					$icon = Feather\Icons::get_base64( 'unlock', 'none', '#E74C3C' );
					if ( 'HTTPS' === strtoupper( $name ) ) {
						$icon = Feather\Icons::get_base64( 'lock', 'none', '#18BB9C' );
					}
					$name  = '<img style="width:14px;vertical-align:text-top;" src="' . $icon . '" />&nbsp;&nbsp;<span class="oemm-table-text">' . strtoupper( $name ) . '</span>';
					$group = 'scheme';
					break;
				case 'methods':
					$name  = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'code', 'none', '#73879C' ) . '" />&nbsp;&nbsp;<span class="oemm-table-text">' . strtoupper( $name ) . '</span>';
					$group = 'verb';
					break;
				case 'countries':
					if ( $other ) {
						$name = esc_html__( 'Other', 'oembed-manager' );
					} else {
						$country_name = L10n::get_country_name( $name );
						if ( $country_name === $name ) {
							$country_name = '';
						}
						$name = '<img style="width:16px;vertical-align:baseline;" src="' . Flagiconcss\Flags::get_base64( strtolower( $name ) ) . '" />&nbsp;&nbsp;<span class="oemm-table-text" style="vertical-align: text-bottom;">' . $country_name . '</span>';
					}
					$group = 'country';
					break;
			}
			$calls = Conversion::number_shorten( $row['sum_hit'], 2, false, '&nbsp;' );
			$in    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-down-right', 'none', '#73879C' ) . '" /><span class="oemm-table-text">' . Conversion::data_shorten( $row['sum_kb_in'] * 1024, 2, false, '&nbsp;' ) . '</span>';
			$out   = '<span class="oemm-table-text">' . Conversion::data_shorten( $row['sum_kb_out'] * 1024, 2, false, '&nbsp;' ) . '</span><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-up-right', 'none', '#73879C' ) . '" />';
			$data  = $in . ' &nbsp;&nbsp; ' . $out;
			if ( 1 < $row['sum_hit'] ) {
				$min = Conversion::number_shorten( $row['min_latency'], 0 );
				if ( false !== strpos( $min, 'K' ) ) {
					$min = str_replace( 'K', esc_html_x( 's', 'Unit symbol - Stands for "second".', 'oembed-manager' ), $min );
				} else {
					$min = $min . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'oembed-manager' );
				}
				$max = Conversion::number_shorten( $row['max_latency'], 0 );
				if ( false !== strpos( $max, 'K' ) ) {
					$max = str_replace( 'K', esc_html_x( 's', 'Unit symbol - Stands for "second".', 'oembed-manager' ), $max );
				} else {
					$max = $max . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'oembed-manager' );
				}
				$latency = (int) $row['avg_latency'] . '&nbsp;' . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'oembed-manager' ) . '&nbsp;<small>(' . $min . '→' . $max . ')</small>';
			} else {
				$latency = (int) $row['avg_latency'] . '&nbsp;' . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'oembed-manager' );
			}
			if ( 'codes' === $type && '0' === $row[ $group ] ) {
				$latency = '-';
			}
			$row_str  = '<tr>';
			$row_str .= '<td data-th="">' . $name . '</td>';
			if ( $has_detail ) {
				$row_str .= '<td data-th="' . $detail_name . '">' . $detail . '</td>';
			}
			$row_str .= '<td data-th="' . $calls_name . '">' . $calls . '</td>';
			$row_str .= '<td data-th="' . $data_name . '">' . $data . '</td>';
			$row_str .= '<td data-th="' . $latency_name . '">' . $latency . '</td>';
			$row_str .= '</tr>';
			if ( $other ) {
				$other_str = $row_str;
			} else {
				$result .= $row_str;
			}
		}
		$result .= $other_str . '</table>';
		return [ 'oemm-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_map() {
		$uuid   = UUID::generate_unique_id( 5 );
		$data   = Schema::get_grouped_list( 'country', [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$series = [];
		foreach ( $data as $datum ) {
			if ( array_key_exists( 'country', $datum ) && ! empty( $datum['country'] ) ) {
				$series[ strtoupper( $datum['country'] ) ] = $datum['sum_hit'];
			}
		}
		$plus    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'plus-square', 'none', '#73879C' ) . '"/>';
		$minus   = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'minus-square', 'none', '#73879C' ) . '"/>';
		$result  = '<div class="oemm-map-handler">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var mapdata' . $uuid . ' = ' . wp_json_encode( $series ) . ';';
		$result .= ' $(".oemm-map-handler").vectorMap({';
		$result .= ' map: "world_mill",';
		$result .= ' backgroundColor: "#FFFFFF",';
		$result .= ' series: {';
		$result .= '  regions: [{';
		$result .= '   values: mapdata' . $uuid . ',';
		$result .= '   scale: ["#BDC7D1", "#73879C"],';
		$result .= '   normalizeFunction: "polynomial"';
		$result .= '  }]';
		$result .= ' },';
		$result .= '  regionStyle: {';
		$result .= '   initial: {fill: "#EEEEEE", "fill-opacity": 0.7},';
		$result .= '   hover: {"fill-opacity": 1,cursor: "default"},';
		$result .= '   selected: {},';
		$result .= '   selectedHover: {},';
		$result .= ' },';
		$result .= ' onRegionTipShow: function(e, el, code){if (mapdata' . $uuid . '[code]){el.html(el.html() + " (" + mapdata' . $uuid . '[code] + " ' . esc_html__( 'calls', 'oembed-manager' ) . ')")};},';
		$result .= ' });';
		$result .= ' $(".jvectormap-zoomin").html(\'' . $plus . '\');';
		$result .= ' $(".jvectormap-zoomout").html(\'' . $minus . '\');';
		$result .= '});';
		$result .= '</script>';
		return [ 'oemm-map' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_chart() {
		$uuid           = UUID::generate_unique_id( 5 );
		$data_total     = Schema::get_time_series( $this->filter, ! $this->is_today, '', [], false );
		$data_uptime    = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', Http::$http_failure_codes, true );
		$data_error     = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', array_diff( Http::$http_error_codes, Http::$http_quota_codes ), false );
		$data_success   = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', Http::$http_success_codes, false );
		$data_quota     = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', Http::$http_quota_codes, false );
		$series_uptime  = [];
		$suc            = [];
		$err            = [];
		$quo            = [];
		$series_success = [];
		$series_error   = [];
		$series_quota   = [];
		$call_max       = 0;
		$kbin           = [];
		$kbout          = [];
		$series_kbin    = [];
		$series_kbout   = [];
		$data_max       = 0;
		$start          = '';
		foreach ( $data_total as $timestamp => $total ) {
			if ( '' === $start ) {
				$start = $timestamp;
			}
			$ts = 'new Date(' . (string) strtotime( $timestamp ) . '000)';
			// Calls.
			if ( array_key_exists( $timestamp, $data_success ) ) {
				$val = $data_success[ $timestamp ]['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$suc[] = [
					'x' => $ts,
					'y' => $val,
				];
			} else {
				$suc[] = [
					'x' => $ts,
					'y' => 0,
				];
			}
			if ( array_key_exists( $timestamp, $data_error ) ) {
				$val = $data_error[ $timestamp ]['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$err[] = [
					'x' => $ts,
					'y' => $val,
				];
			} else {
				$err[] = [
					'x' => $ts,
					'y' => 0,
				];
			}
			if ( array_key_exists( $timestamp, $data_quota ) ) {
				$val = $data_quota[ $timestamp ]['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$quo[] = [
					'x' => $ts,
					'y' => $val,
				];
			} else {
				$quo[] = [
					'x' => $ts,
					'y' => 0,
				];
			}
			// Data.
			$val = $total['sum_kb_in'] * 1024;
			if ( $val > $data_max ) {
				$data_max = $val;
			}
			$kbin[] = [
				'x' => $ts,
				'y' => $val,
			];
			$val    = $total['sum_kb_out'] * 1024;
			if ( $val > $data_max ) {
				$data_max = $val;
			}
			$kbout[] = [
				'x' => $ts,
				'y' => $val,
			];
			// Uptime.
			if ( array_key_exists( $timestamp, $data_uptime ) ) {
				if ( 0 !== $total['sum_hit'] ) {
					$val             = round( $data_uptime[ $timestamp ]['sum_hit'] * 100 / $total['sum_hit'], 2 );
					$series_uptime[] = [
						'x' => $ts,
						'y' => $val,
					];
				} else {
					$series_uptime[] = [
						'x' => $ts,
						'y' => 100,
					];
				}
			} else {
				$series_uptime[] = [
					'x' => $ts,
					'y' => 100,
				];
			}
		}
		$before = [
			'x' => 'new Date(' . (string) ( strtotime( $start ) - 86400 ) . '000)',
			'y' => 'null',
		];
		$after  = [
			'x' => 'new Date(' . (string) ( strtotime( $timestamp ) + 86400 ) . '000)',
			'y' => 'null',
		];
		// Calls.
		$short     = Conversion::number_shorten( $call_max, 2, true );
		$call_max  = 0.5 + floor( $call_max / $short['divisor'] );
		$call_abbr = $short['abbreviation'];
		foreach ( $suc as $item ) {
			$item['y']        = $item['y'] / $short['divisor'];
			$series_success[] = $item;
		}
		foreach ( $err as $item ) {
			$item['y']      = $item['y'] / $short['divisor'];
			$series_error[] = $item;
		}
		foreach ( $quo as $item ) {
			$item['y']      = $item['y'] / $short['divisor'];
			$series_quota[] = $item;
		}
		array_unshift( $series_success, $before );
		array_unshift( $series_error, $before );
		array_unshift( $series_quota, $before );
		$series_success[] = $after;
		$series_error[]   = $after;
		$series_quota[]   = $after;
		$json_call        = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Success', 'oembed-manager' ),
						'data' => $series_success,
					],
					[
						'name' => esc_html__( 'Error', 'oembed-manager' ),
						'data' => $series_error,
					],
					[
						'name' => esc_html__( 'Quota Error', 'oembed-manager' ),
						'data' => $series_quota,
					],
				],
			]
		);
		$json_call        = str_replace( '"x":"new', '"x":new', $json_call );
		$json_call        = str_replace( ')","y"', '),"y"', $json_call );
		$json_call        = str_replace( '"null"', 'null', $json_call );
		// Data.
		$short     = Conversion::data_shorten( $data_max, 2, true );
		$data_max  = (int) ceil( $data_max / $short['divisor'] );
		$data_abbr = $short['abbreviation'];
		foreach ( $kbin as $kb ) {
			$kb['y']       = $kb['y'] / $short['divisor'];
			$series_kbin[] = $kb;
		}
		foreach ( $kbout as $kb ) {
			$kb['y']        = $kb['y'] / $short['divisor'];
			$series_kbout[] = $kb;
		}
		array_unshift( $series_kbin, $before );
		array_unshift( $series_kbout, $before );
		$series_kbin[]  = $after;
		$series_kbout[] = $after;
		$json_data      = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Incoming Data', 'oembed-manager' ),
						'data' => $series_kbin,
					],
					[
						'name' => esc_html__( 'Outcoming Data', 'oembed-manager' ),
						'data' => $series_kbout,
					],
				],
			]
		);
		$json_data      = str_replace( '"x":"new', '"x":new', $json_data );
		$json_data      = str_replace( ')","y"', '),"y"', $json_data );
		$json_data      = str_replace( '"null"', 'null', $json_data );
		// Uptime.
		array_unshift( $series_uptime, $before );
		$series_uptime[] = $after;
		$json_uptime     = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Perceived Uptime', 'oembed-manager' ),
						'data' => $series_uptime,
					],
				],
			]
		);
		$json_uptime     = str_replace( '"x":"new', '"x":new', $json_uptime );
		$json_uptime     = str_replace( ')","y"', '),"y"', $json_uptime );
		$json_uptime     = str_replace( '"null"', 'null', $json_uptime );
		// Rendering.
		if ( 4 < $this->duration ) {
			if ( 1 === $this->duration % 2 ) {
				$divisor = 6;
			} else {
				$divisor = 5;
			}
		} else {
			$divisor = $this->duration + 1;
		}
		$result  = '<div class="oemm-multichart-handler">';
		$result .= '<div class="oemm-multichart-item active" id="oemm-chart-calls">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var call_data' . $uuid . ' = ' . $json_call . ';';
		$result .= ' var call_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var call_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [call_tooltip' . $uuid . '],';
		$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, low: 0, high: ' . $call_max . ', labelInterpolationFnc: function (value) {return value.toString() + " ' . $call_abbr . '";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#oemm-chart-calls", call_data' . $uuid . ', call_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '<div class="oemm-multichart-item" id="oemm-chart-data">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var data_data' . $uuid . ' = ' . $json_data . ';';
		$result .= ' var data_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var data_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [data_tooltip' . $uuid . '],';
		$result .= '  axisX: {type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, low: 0, high: ' . $data_max . ', labelInterpolationFnc: function (value) {return value.toString() + " ' . $data_abbr . '";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#oemm-chart-data", data_data' . $uuid . ', data_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '<div class="oemm-multichart-item" id="oemm-chart-uptime">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var uptime_data' . $uuid . ' = ' . $json_uptime . ';';
		$result .= ' var uptime_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var uptime_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [uptime_tooltip' . $uuid . '],';
		$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " %";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#oemm-chart-uptime", uptime_data' . $uuid . ', uptime_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '</div>';
		return [ 'oemm-main-chart' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   mixed $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_kpi( $queried ) {
		$result = [];
		if ( 'call' === $queried ) {
			$data     = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata    = Schema::get_std_kpi( $this->previous );
			$current  = 0.0;
			$previous = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) && ! empty( $data['sum_hit'] ) ) {
				$current = (float) $data['sum_hit'];
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_hit', $pdata ) && ! empty( $pdata['sum_hit'] ) ) {
				$previous = (float) $pdata['sum_hit'];
			}
			$result[ 'kpi-main-' . $queried ] = Conversion::number_shorten( $current, 1, false, '&nbsp;' );
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			if ( is_array( $data ) && array_key_exists( 'avg_latency', $data ) && ! empty( $data['avg_latency'] ) ) {
				$result[ 'kpi-bottom-' . $queried ] = '<span class="oemm-kpi-large-bottom-text">' . sprintf( esc_html__( 'avg latency: %s ms.', 'oembed-manager' ), (int) $data['avg_latency'] ) . '</span>';
			}
		}
		if ( 'data' === $queried ) {
			$data         = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata        = Schema::get_std_kpi( $this->previous );
			$current_in   = 0.0;
			$current_out  = 0.0;
			$previous_in  = 0.0;
			$previous_out = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_kb_in', $data ) && ! empty( $data['sum_kb_in'] ) ) {
				$current_in = (float) $data['sum_kb_in'] * 1024;
			}
			if ( is_array( $data ) && array_key_exists( 'sum_kb_out', $data ) && ! empty( $data['sum_kb_out'] ) ) {
				$current_out = (float) $data['sum_kb_out'] * 1024;
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_kb_in', $pdata ) && ! empty( $pdata['sum_kb_in'] ) ) {
				$previous_in = (float) $pdata['sum_kb_in'] * 1024;
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_kb_out', $pdata ) && ! empty( $pdata['sum_kb_out'] ) ) {
				$previous_out = (float) $pdata['sum_kb_out'] * 1024;
			}
			$current                          = $current_in + $current_out;
			$previous                         = $previous_in + $previous_out;
			$result[ 'kpi-main-' . $queried ] = Conversion::data_shorten( $current, 1, false, '&nbsp;' );
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			$in                                 = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-down-right', 'none', '#73879C' ) . '" /><span class="oemm-kpi-large-bottom-text">' . Conversion::data_shorten( $current_in, 2, false, '&nbsp;' ) . '</span>';
			$out                                = '<span class="oemm-kpi-large-bottom-text">' . Conversion::data_shorten( $current_out, 2, false, '&nbsp;' ) . '</span><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-up-right', 'none', '#73879C' ) . '" />';
			$result[ 'kpi-bottom-' . $queried ] = $in . ' &nbsp;&nbsp; ' . $out;
		}
		if ( 'server' === $queried || 'quota' === $queried || 'pass' === $queried || 'uptime' === $queried ) {
			$not = false;
			if ( 'server' === $queried ) {
				$codes = Http::$http_error_codes;
			} elseif ( 'quota' === $queried ) {
				$codes = Http::$http_quota_codes;
			} elseif ( 'pass' === $queried ) {
				$codes = Http::$http_effective_pass_codes;
			} elseif ( 'uptime' === $queried ) {
				$codes = Http::$http_failure_codes;
				$not   = true;
			}
			$base        = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pbase       = Schema::get_std_kpi( $this->previous );
			$data        = Schema::get_std_kpi( $this->filter, ! $this->is_today, 'code', $codes, $not );
			$pdata       = Schema::get_std_kpi( $this->previous, true, 'code', $codes, $not );
			$base_value  = 0.0;
			$pbase_value = 0.0;
			$data_value  = 0.0;
			$pdata_value = 0.0;
			$current     = 0.0;
			$previous    = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $base ) && ! empty( $base['sum_hit'] ) ) {
				$base_value = (float) $base['sum_hit'];
			}
			if ( is_array( $pbase ) && array_key_exists( 'sum_hit', $pbase ) && ! empty( $pbase['sum_hit'] ) ) {
				$pbase_value = (float) $pbase['sum_hit'];
			}
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) && ! empty( $data['sum_hit'] ) ) {
				$data_value = (float) $data['sum_hit'];
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_hit', $pdata ) && ! empty( $pdata['sum_hit'] ) ) {
				$pdata_value = (float) $pdata['sum_hit'];
			}
			if ( 0.0 !== $base_value && 0.0 !== $data_value ) {
				$current                          = 100 * $data_value / $base_value;
				$result[ 'kpi-main-' . $queried ] = round( $current, 1 ) . '&nbsp;%';
			} else {
				if ( 0.0 !== $data_value ) {
					$result[ 'kpi-main-' . $queried ] = '100&nbsp;%';
				} elseif ( 0.0 !== $base_value ) {
					$result[ 'kpi-main-' . $queried ] = '0&nbsp;%';
				} else {
					$result[ 'kpi-main-' . $queried ] = '-';
				}
			}
			if ( 0.0 !== $pbase_value && 0.0 !== $pdata_value ) {
				$previous = 100 * $pdata_value / $pbase_value;
			} else {
				if ( 0.0 !== $pdata_value ) {
					$previous = 100.0;
				}
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			switch ( $queried ) {
				case 'server':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="oemm-kpi-large-bottom-text">' . sprintf( esc_html__( '%s calls in error', 'oembed-manager' ), Conversion::number_shorten( $data_value, 2, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'quota':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="oemm-kpi-large-bottom-text">' . sprintf( esc_html__( '%s blocked calls', 'oembed-manager' ), Conversion::number_shorten( $data_value, 2, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'pass':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="oemm-kpi-large-bottom-text">' . sprintf( esc_html__( '%s successful calls', 'oembed-manager' ), Conversion::number_shorten( $data_value, 2, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'uptime':
					if ( 0.0 !== $base_value ) {
						$duration = implode( ', ', Date::get_age_array_from_seconds( $this->duration * DAY_IN_SECONDS * ( 1 - ( $data_value / $base_value ) ), true, true ) );
						if ( '' === $duration ) {
							$duration = esc_html__( 'no downtime', 'oembed-manager' );
						} else {
							$duration = sprintf( esc_html__( 'down %s', 'oembed-manager' ), $duration );
						}
						$result[ 'kpi-bottom-' . $queried ] = '<span class="oemm-kpi-large-bottom-text">' . $duration . '</span>';
					}
					break;
			}
		}
		return $result;
	}

	/**
	 * Get the title selector.
	 *
	 * @return string  The selector ready to print.
	 * @since    1.0.0
	 */
	public function get_title_selector() {
		switch ( $this->type ) {
			case 'domains':
				switch ( $this->extra ) {
					case 'codes':
						$title = esc_html__( 'HTTP Codes Details', 'oembed-manager' );
						break;
					case 'schemes':
						$title = esc_html__( 'Protocols Details', 'oembed-manager' );
						break;
					case 'methods':
						$title = esc_html__( 'Methods Details', 'oembed-manager' );
						break;
					case 'countries':
						$title = esc_html__( 'Countries Details', 'oembed-manager' );
						break;
					default:
						$title = esc_html__( 'Domains Details', 'oembed-manager' );
				}
				break;
			case 'domain':
				$title = esc_html__( 'Domain Summary', 'oembed-manager' );
				break;
			case 'authorities':
				switch ( $this->extra ) {
					case 'codes':
						$title = esc_html__( 'HTTP Codes Details', 'oembed-manager' );
						break;
					case 'schemes':
						$title = esc_html__( 'Protocols Details', 'oembed-manager' );
						break;
					case 'methods':
						$title = esc_html__( 'Methods Details', 'oembed-manager' );
						break;
					case 'countries':
						$title = esc_html__( 'Countries Details', 'oembed-manager' );
						break;
					default:
						$title = esc_html__( 'Domain Details', 'oembed-manager' );
				}
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'oembed-manager' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'oembed-manager' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'authority':
				$title         = esc_html__( 'Subdomain Summary', 'oembed-manager' );
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'oembed-manager' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'oembed-manager' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'endpoints':
				switch ( $this->extra ) {
					case 'codes':
						$title = esc_html__( 'HTTP Codes Details', 'oembed-manager' );
						break;
					case 'schemes':
						$title = esc_html__( 'Protocols Details', 'oembed-manager' );
						break;
					case 'methods':
						$title = esc_html__( 'Methods Details', 'oembed-manager' );
						break;
					case 'countries':
						$title = esc_html__( 'Countries Details', 'oembed-manager' );
						break;
					default:
						$title = esc_html__( 'Subdomain Details', 'oembed-manager' );
				}
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Subdomain Summary', 'oembed-manager' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'oembed-manager' ), $this->subdomain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'authority',
							'domain' => $this->domain,
							'id'     => $this->subdomain,
						]
					),
				];
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'oembed-manager' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'oembed-manager' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'endpoint':
				$title         = esc_html__( 'Endpoint Summary', 'oembed-manager' );
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Subdomain Summary', 'oembed-manager' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'oembed-manager' ), $this->subdomain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'authority',
							'domain' => $this->domain,
							'id'     => $this->subdomain,
						]
					),
				];
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'oembed-manager' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'oembed-manager' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'country':
				$title    = esc_html__( 'Country', 'oembed-manager' );
				$subtitle = L10n::get_country_name( $this->id );
				break;

		}
		$breadcrumbs[] = [
			'title'    => esc_html__( 'Main Summary', 'oembed-manager' ),
			'subtitle' => sprintf( esc_html__( 'Return to oEmbed Manager main page.', 'oembed-manager' ) ),
			'url'      => $this->get_url( [ 'domain', 'id', 'extra', 'type' ] ),
		];
		$result        = '<select name="sources" id="sources" class="oemm-select sources" placeholder="' . $title . '" style="display:none;">';
		foreach ( $breadcrumbs as $breadcrumb ) {
			$result .= '<option value="' . $breadcrumb['url'] . '">' . $breadcrumb['title'] . '~-' . $breadcrumb['subtitle'] . '-~</span></option>';
		}
		$result .= '</select>';
		$result .= '';

		return $result;
	}

	/**
	 * Get the site selection bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_site_bar() {
		if ( Role::SINGLE_ADMIN === Role::admin_type() ) {
			return '';
		}
		if ( 'all' === $this->site ) {
			$result = '<span class="oemm-site-text">' . esc_html__( 'All Sites', 'oembed-manager' ) . '</span>';
		} else {
			if ( Role::SUPER_ADMIN === Role::admin_type() ) {
				$quit   = '<a href="' . esc_url( $this->get_url( [ 'site' ] ) ) . '"><img style="width:12px;vertical-align:text-top;" src="' . Feather\Icons::get_base64( 'x-circle', 'none', '#FFFFFF' ) . '" /></a>';
				$result = '<span class="oemm-site-button">' . sprintf( esc_html__( 'Site ID %s', 'oembed-manager' ), $this->site ) . $quit . '</span>';
			} else {
				$result = '<span class="oemm-site-text">' . sprintf( esc_html__( 'Site ID %s', 'oembed-manager' ), $this->site ) . '</span>';
			}
		}
		return '<span class="oemm-site">' . $result . '</span>';
	}

	/**
	 * Get the title bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_title_bar() {
		$subtitle = $this->id;
		switch ( $this->type ) {
			case 'summary':
				$title = esc_html__( 'Main Summary', 'oembed-manager' );
				break;
			case 'domain':
			case 'authority':
			case 'endpoint':
			case 'domains':
			case 'authorities':
			case 'endpoints':
				$title = $this->get_title_selector();
				break;
		}
		$result  = '<div class="oemm-box oemm-box-full-line">';
		$result .= $this->get_site_bar();
		$result .= '<span class="oemm-title">' . $title . '</span>';
		$result .= '<span class="oemm-subtitle">' . $subtitle . '</span>';
		$result .= '<span class="oemm-datepicker">' . $this->get_date_box() . '</span>';
		$result .= '<span class="oemm-switch">' . $this->get_switch_box( 'inbound' ) . '</span>';
		$result .= '<span class="oemm-switch">' . $this->get_switch_box( 'outbound' ) . '</span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the KPI bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_kpi_bar() {
		$result  = '<div class="oemm-box oemm-box-full-line">';
		$result .= '<div class="oemm-kpi-bar">';
		$result .= '<div class="oemm-kpi-large">' . $this->get_large_kpi( 'call' ) . '</div>';
		$result .= '<div class="oemm-kpi-large">' . $this->get_large_kpi( 'data' ) . '</div>';
		$result .= '<div class="oemm-kpi-large">' . $this->get_large_kpi( 'server' ) . '</div>';
		$result .= '<div class="oemm-kpi-large">' . $this->get_large_kpi( 'quota' ) . '</div>';
		$result .= '<div class="oemm-kpi-large">' . $this->get_large_kpi( 'pass' ) . '</div>';
		$result .= '<div class="oemm-kpi-large">' . $this->get_large_kpi( 'uptime' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the main chart.
	 *
	 * @return string  The main chart ready to print.
	 * @since    1.0.0
	 */
	public function get_main_chart() {
		if ( 1 < $this->duration ) {
			$help_calls  = esc_html__( 'Responses types distribution.', 'oembed-manager' );
			$help_data   = esc_html__( 'Data volume distribution.', 'oembed-manager' );
			$help_uptime = esc_html__( 'Uptime variation.', 'oembed-manager' );
			$detail      = '<span class="oemm-chart-button not-ready left" id="oemm-chart-button-calls" data-position="left" data-tooltip="' . $help_calls . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'hash', 'none', '#73879C' ) . '" /></span>';
			$detail     .= '&nbsp;&nbsp;&nbsp;<span class="oemm-chart-button not-ready left" id="oemm-chart-button-data" data-position="left" data-tooltip="' . $help_data . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'link-2', 'none', '#73879C' ) . '" /></span>&nbsp;&nbsp;&nbsp;';
			$detail     .= '<span class="oemm-chart-button not-ready left" id="oemm-chart-button-uptime" data-position="left" data-tooltip="' . $help_uptime . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'activity', 'none', '#73879C' ) . '" /></span>';
			$result      = '<div class="oemm-row">';
			$result     .= '<div class="oemm-box oemm-box-full-line">';
			$result     .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'Metrics Variations', 'oembed-manager' ) . '<span class="oemm-module-more">' . $detail . '</span></span></div>';
			$result     .= '<div class="oemm-module-content" id="oemm-main-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
			$result     .= '</div>';
			$result     .= '</div>';
			$result     .= $this->get_refresh_script(
				[
					'query'   => 'main-chart',
					'queried' => 0,
				]
			);
			return $result;
		} else {
			return '';
		}
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_sites_list() {
		$result  = '<div class="oemm-box oemm-box-full-line">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'Sites Breakdown', 'oembed-manager' ) . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-sites">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'sites',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_domains_list() {
		$result  = '<div class="oemm-box oemm-box-full-line">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'All Domains', 'oembed-manager' ) . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-domains">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'domains',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the authorities list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_authorities_list() {
		$result  = '<div class="oemm-box oemm-box-full-line">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'All Subdomains', 'oembed-manager' ) . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-authorities">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'authorities',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the endpoints list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_endpoints_list() {
		$result  = '<div class="oemm-box oemm-box-full-line">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'All Endpoints', 'oembed-manager' ) . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'endpoints',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the extra list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_extra_list() {
		switch ( $this->extra ) {
			case 'codes':
				$title = esc_html__( 'All HTTP Codes', 'oembed-manager' );
				break;
			case 'schemes':
				$title = esc_html__( 'All Protocols', 'oembed-manager' );
				break;
			case 'methods':
				$title = esc_html__( 'All Methods', 'oembed-manager' );
				break;
			case 'countries':
				$title = esc_html__( 'All Countries', 'oembed-manager' );
				break;
			default:
				$title = esc_html__( 'All Endpoints', 'oembed-manager' );
		}
		$result  = '<div class="oemm-box oemm-box-full-line">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . $title . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-' . $this->extra . '">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->extra,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the top domains box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_domain_box() {
		$url     = $this->get_url( [ 'domain' ], [ 'type' => 'domains' ] );
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all domains.', 'oembed-manager' );
		$result  = '<div class="oemm-40-module">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'Top Domains', 'oembed-manager' ) . '</span><span class="oemm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-top-domains">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-domains',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the top authority box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_authority_box() {
		$url     = $this->get_url(
			[],
			[
				'type'   => 'authorities',
				'domain' => $this->domain,
			]
		);
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all subdomains.', 'oembed-manager' );
		$result  = '<div class="oemm-40-module">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'Top Subdomains', 'oembed-manager' ) . '</span><span class="oemm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-top-authorities">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-authorities',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the top endpoint box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_endpoint_box() {
		$url     = $this->get_url(
			[],
			[
				'type'   => 'endpoints',
				'domain' => $this->domain,
			]
		);
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all endpoints.', 'oembed-manager' );
		$result  = '<div class="oemm-40-module">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'Top Endpoints', 'oembed-manager' ) . '</span><span class="oemm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-top-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-endpoints',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_map_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'countries',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'countries',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'countries',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all countries.', 'oembed-manager' );
		$result  = '<div class="oemm-60-module">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'Countries', 'oembed-manager' ) . '</span><span class="oemm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-map">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'map',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_codes_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'codes',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'codes',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'codes',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all codes.', 'oembed-manager' );
		$result  = '<div class="oemm-33-module oemm-33-left-module">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'HTTP codes', 'oembed-manager' ) . '</span><span class="oemm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-code">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'code',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_security_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'schemes',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'schemes',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'schemes',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of protocols breakdown.', 'oembed-manager' );
		$result  = '<div class="oemm-33-module oemm-33-center-module">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'Protocols', 'oembed-manager' ) . '</span><span class="oemm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-security">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'security',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_method_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'methods',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'methods',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'methods',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all methods.', 'oembed-manager' );
		$result  = '<div class="oemm-33-module oemm-33-right-module">';
		$result .= '<div class="oemm-module-title-bar"><span class="oemm-module-title">' . esc_html__( 'Methods', 'oembed-manager' ) . '</span><span class="oemm-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="oemm-module-content" id="oemm-method">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'method',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get a large kpi box.
	 *
	 * @param   string $kpi     The kpi to render.
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_large_kpi( $kpi ) {
		switch ( $kpi ) {
			case 'call':
				$icon  = Feather\Icons::get_base64( 'hash', 'none', '#73879C' );
				$title = esc_html_x( 'Number of Calls', 'Noun - Number API calls.', 'oembed-manager' );
				$help  = esc_html__( 'Number of API calls.', 'oembed-manager' );
				break;
			case 'data':
				$icon  = Feather\Icons::get_base64( 'link-2', 'none', '#73879C' );
				$title = esc_html_x( 'Data Volume', 'Noun - Volume of transferred data.', 'oembed-manager' );
				$help  = esc_html__( 'Volume of transferred data.', 'oembed-manager' );
				break;
			case 'server':
				$icon  = Feather\Icons::get_base64( 'x-octagon', 'none', '#73879C' );
				$title = esc_html_x( 'Server Error Rate', 'Noun - Ratio of the number of HTTP errors to the total number of calls.', 'oembed-manager' );
				$help  = esc_html__( 'Ratio of the number of HTTP errors to the total number of calls.', 'oembed-manager' );
				break;
			case 'quota':
				$icon  = Feather\Icons::get_base64( 'shield-off', 'none', '#73879C' );
				$title = esc_html_x( 'Quotas Error Rate', 'Noun - Ratio of the quota enforcement number to the total number of calls.', 'oembed-manager' );
				$help  = esc_html__( 'Ratio of the quota enforcement number to the total number of calls.', 'oembed-manager' );
				break;
			case 'pass':
				$icon  = Feather\Icons::get_base64( 'check-circle', 'none', '#73879C' );
				$title = esc_html_x( 'Effective Pass Rate', 'Noun - Ratio of the number of HTTP success to the total number of calls.', 'oembed-manager' );
				$help  = esc_html__( 'Ratio of the number of HTTP success to the total number of calls.', 'oembed-manager' );
				break;
			case 'uptime':
				$icon  = Feather\Icons::get_base64( 'activity', 'none', '#73879C' );
				$title = esc_html_x( 'Perceived Uptime', 'Noun - Perceived uptime, from the viewpoint of the site.', 'oembed-manager' );
				$help  = esc_html__( 'Perceived uptime, from the viewpoint of the site.', 'oembed-manager' );
				break;
		}
		$top       = '<img style="width:12px;vertical-align:baseline;" src="' . $icon . '" />&nbsp;&nbsp;<span style="cursor:help;" class="oemm-kpi-large-top-text bottom" data-position="bottom" data-tooltip="' . $help . '">' . $title . '</span>';
		$indicator = '&nbsp;';
		$bottom    = '<span class="oemm-kpi-large-bottom-text">&nbsp;</span>';
		$result    = '<div class="oemm-kpi-large-top">' . $top . '</div>';
		$result   .= '<div class="oemm-kpi-large-middle"><div class="oemm-kpi-large-middle-left" id="kpi-main-' . $kpi . '">' . $this->get_value_placeholder() . '</div><div class="oemm-kpi-large-middle-right" id="kpi-index-' . $kpi . '">' . $indicator . '</div></div>';
		$result   .= '<div class="oemm-kpi-large-bottom" id="kpi-bottom-' . $kpi . '">' . $bottom . '</div>';
		$result   .= $this->get_refresh_script(
			[
				'query'   => 'kpi',
				'queried' => $kpi,
			]
		);
		return $result;
	}

	/**
	 * Get a placeholder for graph.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder( $height ) {
		return '<p style="text-align:center;line-height:' . $height . 'px;"><img style="width:40px;vertical-align:middle;" src="' . OEMM_ADMIN_URL . 'medias/bars.svg" /></p>';
	}

	/**
	 * Get a placeholder for value.
	 *
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_value_placeholder() {
		return '<img style="width:26px;vertical-align:middle;" src="' . OEMM_ADMIN_URL . 'medias/three-dots.svg" />';
	}

	/**
	 * Get refresh script.
	 *
	 * @param   array $args Optional. The args for the ajax call.
	 * @return string  The script, ready to print.
	 * @since    1.0.0
	 */
	private function get_refresh_script( $args = [] ) {
		$result  = '<script>';
		$result .= 'jQuery(document).ready( function($) {';
		$result .= ' var data = {';
		$result .= '  action:"oemm_get_stats",';
		$result .= '  nonce:"' . wp_create_nonce( 'ajax_oemm' ) . '",';
		foreach ( $args as $key => $val ) {
			$s = '  ' . $key . ':';
			if ( is_string( $val ) ) {
				$s .= '"' . $val . '"';
			} elseif ( is_numeric( $val ) ) {
				$s .= $val;
			} elseif ( is_bool( $val ) ) {
				$s .= $val ? 'true' : 'false';
			}
			$result .= $s . ',';
		}
		if ( '' !== $this->id ) {
			$result .= '  id:"' . $this->id . '",';
		}
		$result .= '  type:"' . $this->type . '",';
		if ( '' !== $this->context ) {
			$result .= '  context:"' . $this->context . '",';
		}
		$result .= '  site:"' . $this->site . '",';
		$result .= '  start:"' . $this->start . '",';
		$result .= '  end:"' . $this->end . '",';
		$result .= ' };';
		$result .= ' $.post(ajaxurl, data, function(response) {';
		$result .= ' var val = JSON.parse(response);';
		$result .= ' $.each(val, function(index, value) {$("#" + index).html(value);});';
		if ( array_key_exists( 'query', $args ) && 'main-chart' === $args['query'] ) {
			$result .= '$(".oemm-chart-button").removeClass("not-ready");';
			$result .= '$("#oemm-chart-button-calls").addClass("active");';
		}
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

	/**
	 * Get the url.
	 *
	 * @param   array $exclude Optional. The args to exclude.
	 * @param   array $replace Optional. The args to replace or add.
	 * @return string  The url.
	 * @since    1.0.0
	 */
	private function get_url( $exclude = [], $replace = [] ) {
		$params           = [];
		$params['type']   = $this->type;
		$params['site']   = $this->site;
		$params['domain'] = $this->domain;
		if ( '' !== $this->id ) {
			$params['id'] = $this->id;
		}
		if ( '' !== $this->extra ) {
			$params['extra'] = $this->extra;
		}
		$params['start'] = $this->start;
		$params['end']   = $this->end;
		if ( ! ( $this->is_inbound && $this->is_outbound ) ) {
			if ( $this->is_inbound ) {
				$params['context'] = 'inbound';
			}
			if ( $this->is_outbound ) {
				$params['context'] = 'outbound';
			}
		}
		foreach ( $exclude as $arg ) {
			unset( $params[ $arg ] );
		}
		foreach ( $replace as $key => $arg ) {
			$params[ $key ] = $arg;
		}
		$url = admin_url( 'admin.php?page=oemm-viewer' );
		foreach ( $params as $key => $arg ) {
			if ( '' !== $arg ) {
				$url .= '&' . $key . '=' . $arg;
			}
		}
		return $url;
	}

	/**
	 * Get a large kpi box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_switch_box( $bound ) {
		$enabled = false;
		$other   = false;
		$other_t = 'both';
		if ( 'inbound' === $bound ) {
			$enabled = $this->has_inbound;
			$other   = $this->is_outbound;
			$other_t = 'outbound';
		}
		if ( 'outbound' === $bound ) {
			$enabled = $this->has_outbound;
			$other   = $this->is_inbound;
			$other_t = 'inbound';
		}
		if ( $enabled ) {
			$opacity = '';
			if ( 'inbound' === $bound ) {
				$checked = $this->is_inbound;
			}
			if ( 'outbound' === $bound ) {
				$checked = $this->is_outbound;
			}
		} else {
			$opacity = ' style="opacity:0.4"';
			$checked = false;
		}
		$result = '<input type="checkbox" class="oemm-input-' . $bound . '-switch"' . ( $checked ? ' checked' : '' ) . ' />';
		// phpcs:ignore
		$result .= '&nbsp;<span class="oemm-text-' . $bound . '-switch"' . $opacity . '>' . esc_html__( $bound, 'oembed-manager' ) . '</span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var elem = document.querySelector(".oemm-input-' . $bound . '-switch");';
		$result .= ' var params = {size: "small", color: "#5A738E", disabledOpacity:0.6 };';
		$result .= ' var ' . $bound . ' = new Switchery(elem, params);';
		if ( $enabled ) {
			$result .= ' ' . $bound . '.enable();';
		} else {
			$result .= ' ' . $bound . '.disable();';
		}
		$result .= ' elem.onchange = function() {';
		$result .= '  var url="' . $this->get_url( [ 'context' ], [ 'domain' => $this->domain ] ) . '";';
		if ( $other ) {
			$result .= ' if (!elem.checked) {url = url + "&context=' . $other_t . '";}';
		} else {
			$result .= ' if (elem.checked) {url = url + "&context=' . $other_t . '";}';
		}
		$result .= '  $(location).attr("href", url);';
		$result .= ' };';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

	/**
	 * Get a date picker box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_date_box() {
		$result  = '<img style="width:13px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'calendar', 'none', '#5A738E' ) . '" />&nbsp;&nbsp;<span class="oemm-datepicker-value"></span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' moment.locale("' . L10n::get_display_locale() . '");';
		$result .= ' var start = moment("' . $this->start . '");';
		$result .= ' var end = moment("' . $this->end . '");';
		$result .= ' function changeDate(start, end) {';
		$result .= '  $("span.oemm-datepicker-value").html(start.format("LL") + " - " + end.format("LL"));';
		$result .= ' }';
		$result .= ' $(".oemm-datepicker").daterangepicker({';
		$result .= '  opens: "left",';
		$result .= '  startDate: start,';
		$result .= '  endDate: end,';
		$result .= '  minDate: moment("' . Schema::get_oldest_date() . '"),';
		$result .= '  maxDate: moment(),';
		$result .= '  showCustomRangeLabel: true,';
		$result .= '  alwaysShowCalendars: true,';
		$result .= '  locale: {customRangeLabel: "' . esc_html__( 'Custom Range', 'oembed-manager' ) . '",cancelLabel: "' . esc_html__( 'Cancel', 'oembed-manager' ) . '", applyLabel: "' . esc_html__( 'Apply', 'oembed-manager' ) . '"},';
		$result .= '  ranges: {';
		$result .= '    "' . esc_html__( 'Today', 'oembed-manager' ) . '": [moment(), moment()],';
		$result .= '    "' . esc_html__( 'Yesterday', 'oembed-manager' ) . '": [moment().subtract(1, "days"), moment().subtract(1, "days")],';
		$result .= '    "' . esc_html__( 'This Month', 'oembed-manager' ) . '": [moment().startOf("month"), moment().endOf("month")],';
		$result .= '    "' . esc_html__( 'Last Month', 'oembed-manager' ) . '": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],';
		$result .= '  }';
		$result .= ' }, changeDate);';
		$result .= ' changeDate(start, end);';
		$result .= ' $(".oemm-datepicker").on("apply.daterangepicker", function(ev, picker) {';
		$result .= '  var url = "' . $this->get_url( [ 'start', 'end' ], [ 'domain' => $this->domain ] ) . '" + "&start=" + picker.startDate.format("YYYY-MM-DD") + "&end=" + picker.endDate.format("YYYY-MM-DD");';
		$result .= '  $(location).attr("href", url);';
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

}
