<?php
require 'config.php';

$path_prefix = '';

if ( isset( $_SERVER['PATH_INFO'] ) ) {
	$path_count = substr_count( $_SERVER['PATH_INFO'], '/' ) - 1;

	for ( $i = 0; $i < $path_count; $i++ ) {
		$path_prefix .= '../';
	}

	if ( strpos( $_SERVER['PATH_INFO'], '/api/stock-prices' ) !== false ) {
		try {
			$db = new PDO( 'sqlite:database.db' );
		} catch ( PDOException $e ) {
			exit( $e->getMessage() );
		}

		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			$input = str_replace( 'stock=', 'stock[]=', $_SERVER['QUERY_STRING'] );
			parse_str( $input, $data );

			if ( empty( $data['stock'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'stock is required',
				] );
				exit;
			}

			if ( count( $data['stock'] ) > 2 ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'only 1 or 2 stock is supported',
				] );
				exit;
			}

			$stocks = [];

			foreach ( $data['stock'] as $stock ) {
				$stock = strtoupper( $stock );

				$stock_data = get_stock( $stock );

				if ( ! $stock_data ) {
					add_stock( $stock );
				}

				if ( isset( $data['like'] ) ) {
					$data['like'] = $data['like'] === 'true';

					if ( $data['like'] ) {
						add_stock_like( $stock );
					}
				}

				$stock_data = get_stock( $stock );

				$stocks[] = [
					'stock' => $stock,
					'price' => get_stock_price( $stock ),
					'likes' => $stock_data['likes'],
				];
			}

			if ( count( $data['stock'] ) === 2 ) {
				$stocks[0]['rel_likes'] = $stocks[0]['likes'] - $stocks[1]['likes'];
				$stocks[1]['rel_likes'] = $stocks[1]['likes'] - $stocks[0]['likes'];
			}

			header( 'Content-Type: application/json; charset=utf-8' );
			echo json_encode( $stocks );
			exit;
		} else {
			redirect_to_index();
		}
	} elseif ( strpos( $_SERVER['PATH_INFO'], '/api/test' ) !== false ) {
		$tests = [];

		$send_data = [
			'stock' => 'goog',
		];
		$data = get_api_data( '/api/stock-prices?' . http_build_query( $send_data ) );
		$tests[] = [
			'title' => 'GET /api/stock-prices => object: 1 stock',
			'data' => $send_data,
			'passed' => (
				isset( $data[0]['stock'] )
				&&
				$data[0]['stock'] == strtoupper( $send_data['stock'] )
				&&
				isset( $data[0]['price'] )
				&&
				isset( $data[0]['likes'] )
			),
		];

		$send_data = [
			'stock' => 'goog',
			'like' => 'true',
		];
		$data = get_api_data( '/api/stock-prices?' . http_build_query( $send_data ) );
		$tests[] = [
			'title' => 'GET /api/stock-prices => object: 1 stock with like',
			'data' => $send_data,
			'passed' => (
				isset( $data[0]['stock'] )
				&&
				$data[0]['stock'] == strtoupper( $send_data['stock'] )
				&&
				isset( $data[0]['price'] )
				&&
				isset( $data[0]['likes'] )
				&&
				$data[0]['likes'] > 0
			),
		];
		$likes = $data[0]['likes'];

		$send_data = [
			'stock' => 'goog',
			'like' => 'true',
		];
		$data = get_api_data( '/api/stock-prices?' . http_build_query( $send_data ) );
		$tests[] = [
			'title' => "GET /api/stock-prices => object: 1 stock with like again (ensure likes are not double counted)",
			'data' => $send_data,
			'passed' => (
				isset( $data[0]['stock'] )
				&&
				$data[0]['stock'] == strtoupper( $send_data['stock'] )
				&&
				isset( $data[0]['price'] )
				&&
				isset( $data[0]['likes'] )
				&&
				$data[0]['likes'] === $likes
			),
		];

		$send_data = [
			'stock' => [ 'goog', 'msft' ],
		];
		$data = get_api_data( '/api/stock-prices?' . http_build_query( $send_data ) );
		$tests[] = [
			'title' => 'GET /api/stock-prices => object: 2 stocks',
			'data' => $send_data,
			'passed' => (
				isset( $data[0]['stock'] )
				&&
				$data[0]['stock'] == strtoupper( $send_data['stock'][0] )
				&&
				isset( $data[0]['price'] )
				&&
				isset( $data[0]['rel_likes'] )
				&&
				isset( $data[1]['stock'] )
				&&
				$data[1]['stock'] == strtoupper( $send_data['stock'][1] )
				&&
				isset( $data[1]['price'] )
				&&
				isset( $data[1]['rel_likes'] )
				&&
				$data[0]['rel_likes'] + $data[1]['rel_likes'] === 0
			),
		];
		$rel_likes = abs( $data[0]['rel_likes'] );

		$send_data = [
			'stock' => [ 'goog', 'msft' ],
			'like' => 'true',
		];
		$data = get_api_data( '/api/stock-prices?' . http_build_query( $send_data ) );
		$tests[] = [
			'title' => 'GET /api/stock-prices => object: 2 stocks with like',
			'data' => $send_data,
			'passed' => (
				isset( $data[0]['stock'] )
				&&
				$data[0]['stock'] == strtoupper( $send_data['stock'][0] )
				&&
				isset( $data[0]['price'] )
				&&
				isset( $data[0]['rel_likes'] )
				&&
				isset( $data[1]['stock'] )
				&&
				$data[1]['stock'] == strtoupper( $send_data['stock'][1] )
				&&
				isset( $data[1]['price'] )
				&&
				isset( $data[1]['rel_likes'] )
				&&
				$data[0]['rel_likes'] + $data[1]['rel_likes'] === 0
				&&
				abs( $data[0]['rel_likes'] ) === $rel_likes
			),
		];

		header( 'Content-Type: application/json; charset=utf-8' );
		echo json_encode( $tests );
		exit;
	} else {
		redirect_to_index();
	}
}

function redirect_to_index() {
	global $path_prefix;

	if ( $path_prefix == '' ) {
		$path_prefix = './';
	}

	header( "Location: $path_prefix" );
	exit;
}

function get_api_data( $path ) {
	$url = 'http' . ( ! empty( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

	if ( isset( $_SERVER['PATH_INFO'] ) ) {
		$url = str_replace( $_SERVER['PATH_INFO'], '', $url ) . '/';
	}

	$url .= ltrim( $path, '/' );

	$ch = curl_init( $url );

	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

	$result = curl_exec( $ch );

	$return = $result ? json_decode( $result, true ) : [];

	curl_close( $ch );

	return $return;
}

function get_stock_price( $stock ) {
	$stock = strtolower( $stock );
	$API_KEY = API_KEY;

	$url = "https://www.alphavantage.co/query?function=global_quote&symbol=$stock&apikey=$API_KEY";

	$ch = curl_init( $url );

	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

	$result = curl_exec( $ch );

	curl_close( $ch );

	$result = $result ? json_decode( $result, true ) : [];
	$return = isset( $result['Global Quote']['05. price'] ) ? (float) $result['Global Quote']['05. price'] : 0;

	return $return;
}

function get_stock( $stock, $count_likes = true ) {
	global $db;

	$query = $db->query( "SELECT * FROM stocks WHERE stock = {$db->quote( $stock )}" );
	$result = $query->fetchAll( PDO::FETCH_ASSOC );

	if ( $result ) {
		$result[0]['likes'] = json_decode( $result[0]['likes'], true );

		if ( $count_likes ) {
			$result[0]['likes'] = count( $result[0]['likes'] );
		}
	}

	return $result ? $result[0] : false;
}

function add_stock( $stock ) {
	global $db;

	$data = [
		'stock' => $stock,
	];
	$sth = $db->prepare( 'INSERT INTO stocks (stock) VALUES (:stock)' );
	return $sth->execute( $data );
}

function add_stock_like( $stock ) {
	global $db;

	$stock = get_stock( $stock, false );

	if ( $stock && ! in_array( $_SERVER['REMOTE_ADDR'], $stock['likes'] ) ) {
		$stock['likes'][] = $_SERVER['REMOTE_ADDR'];

		$data = [
			'stock' => $stock['stock'],
			'likes' => json_encode( $stock['likes'] ),
		];
		$sth = $db->prepare( 'UPDATE stocks SET likes = :likes WHERE stock = :stock' );
		return $sth->execute( $data );
	} else {
		return false;
	}
}
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Nasdaq Stock Price Checker</title>
	<meta name="description" content="freeCodeCamp - Information Security and Quality Assurance Project: Stock Price Checker">
	<link rel="icon" type="image/x-icon" href="<?php echo $path_prefix; ?>favicon.ico">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/style.min.css">
	<script src="<?php echo $path_prefix; ?>assets/js/script.min.js"></script>
</head>
<body>
	<div class="container">
		<div class="p-4 my-4 bg-light rounded-3">
			<div class="row">
				<div class="col">
					<header>
						<h1 id="title" class="text-center">Nasdaq Stock Price Checker</h1>
					</header>

					<div id="userstories">
						<h3>User Stories:</h3>
						<ol>
							<li>I can <b>GET</b> <code>/api/stock-prices</code> with form data containing a Nasdaq <i>stock</i> ticker and recieve back an <i>object</i>.</li>
							<li>In the recieved <i>object</i>, I can see the <i>stock</i> (string, the ticker), <i>price</i> (number), and <i>likes</i> (number).</li>
							<li>I can also pass along field <i>like</i> as <b>true</b> (boolean) to have my like added to the stock(s). Only 1 like per IP address should be accepted.</li>
							<li>If I pass along 2 stocks, the returned object will be an array with both stock's info and in addition to <i>likes</i>, it will also contain <i>rel_likes</i> (the difference between the likes on both) on both.</li>
							<li>A good way to receive the current price is the following external API (replace 'stock' with the stock ticker and 'API_KEY' with the API key):<br> <code>https://www.alphavantage.co/query?function=global_quote&symbol=stock&apikey=API_KEY</code><br> (Get your free API key <a href="https://www.alphavantage.co/support/#api-key" target="_blank">here</a>)</li>
							<li>All 5 <a href="<?php echo $path_prefix; ?>api/test" target="_blank">tests</a> are complete and passing.</li>
						</ol>
						<h3>Example usage:</h3>
						<ul>
							<li><code><a href="<?php echo $path_prefix; ?>api/stock-prices?stock=goog" target="_blank">/api/stock-prices?stock=goog</a></code></li>
							<li><code><a href="<?php echo $path_prefix; ?>api/stock-prices?stock=goog&like=true" target="_blank">/api/stock-prices?stock=goog&amp;like=true</a></code></li>
							<li><code><a href="<?php echo $path_prefix; ?>api/stock-prices?stock=msft" target="_blank">/api/stock-prices?stock=msft</a></code></li>
							<li><code><a href="<?php echo $path_prefix; ?>api/stock-prices?stock=msft&like=true" target="_blank">/api/stock-prices?stock=msft&amp;like=true</a></code></li>
							<li><code><a href="<?php echo $path_prefix; ?>api/stock-prices?stock=goog&stock=msft" target="_blank">/api/stock-prices?stock=goog&amp;stock=msft</a></code></li>
							<li><code><a href="<?php echo $path_prefix; ?>api/stock-prices?stock=goog&stock=msft&like=true" target="_blank">/api/stock-prices?stock=goog&amp;stock=msft&amp;like=true</a></code></li>
						</ul>
						<h3>Example return:</h3>
						<p>
							<code>[ { "stock": "GOOG", "price": 0, "likes": 0 } ]</code><br>
							<code>[ { "stock": "GOOG", "price": 0, "likes": 0, "rel_likes": 0 }, { "stock": "MSFT", "price": 0, "likes": 0, "rel_likes": 0 } ]</code>
						</p>
					</div>

					<hr>

					<div id="testui">
						<h2>Front-End:</h2>
						<div class="row">
							<div class="col">
								<h3>Get single price and total likes</h3>
								<form class="test-form d-flex align-items-center">
									<input type="text" class="form-control me-2" name="stock" placeholder="goog" required>
									<div class="form-check me-2">
										<input type="checkbox" name="like" id="like-checkbox" class="form-check-input" value="true">
										<label for="like-checkbox" class="form-check-label">Like</label>
									</div>
									<button type="submit" class="btn btn-primary">Get Price</button>
								</form>
							</div>
							<div class="col">
								<h3>Compare and get relative likes</h3>
								<form class="test-form d-flex align-items-center">
									<input type="text" class="form-control me-2" name="stock" placeholder="goog" required>
									<input type="text" class="form-control me-2" name="stock" placeholder="msft" required>
									<div class="form-check me-2">
										<input type="checkbox" name="like" id="like-both-checkbox" class="form-check-input" value="true">
										<label for="like-both-checkbox" class="form-check-label">Like both</label>
									</div>
									<button type="submit" class="btn btn-primary">Get Price</button>
								</form>
							</div>
						</div>

						<p class="mt-2">
							<code id="result-json"></code>
						</p>
					</div>

					<hr>

					<div class="footer text-center"><a href="https://www.alphavantage.co/documentation/#latestprice" target="_blank">Data</a> provided by <a href="https://www.alphavantage.co" target="_blank">Alpha Vantage</a><br>by <a href="https://www.freecodecamp.org" target="_blank">freeCodeCamp</a> (ISQA5) & <a href="https://www.freecodecamp.org/adam777" target="_blank">Adam</a> | <a href="https://github.com/Adam777Z/freecodecamp-project-stock-checker-php" target="_blank">GitHub</a></div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>