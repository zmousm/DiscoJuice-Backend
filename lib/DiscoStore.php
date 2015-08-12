<?php


abstract class DiscoStore {


	function __construct() {

	}



	function logProcess($feedId, $timer) {

	}

	function insert(FeedItem $item) {

	}

	function update(FeedItem $item) {

	}

	function listFeedEntities($feed) {

	}

	function insertOrUpdate(FeedItem $item) {


	}

	function remove($feed, $entityId) {

	}


	function getFeed($feed) {

	}

	function getFeedMetadata($feed) {

	}

	function getPipe($id) {

	}

	function getFeedList() {

	}

	function getIdPs($query) {


	}

	function insertOrUpdateFeed($item) {


	}

}