<?php

//add query logging
if( defined('WP_DEBUG') ) define('SAVEQUERIES', true);

/**
 * Simple helper class to build MySQL query strings, chaining
 * TODO: sanitizing input - use CONDITION
 * TODO: array inputs
 * TODO: use wpdb->prepare?
 * @author jeremys
 *
 *
 * @usage
	$qb	->select('DISTINCT p.*')
	->from("$wpdb->posts p, $wpdb->term_relationships tr, $wpdb->terms t, $wpdb->term_taxonomy tt")
	->where('p.ID = tr.object_id')
	->where('t.term_id = tt.term_id')
	->where('tt.term_taxonomy_id = tr.term_taxonomy_id')
	->where( $qb->prepare('p.post_type = %s', $type) )
	->where( $qb->prepare(true,'t.slug = %s AND t.customparam2 = %s', $term, $term2) )
	->where( $qb->prepare('p.ID = %d', $id) )
	->start_nested_where('status')
		//->nested_where('status', "p.post_status = 'draft'", 'OR')
		->nested_where('status', "p.post_status = 'future'", 'OR')
		->nested_where('status', "p.post_status = 'publish'", 'OR')
	->like('m.meta_value', '%NC%')
	->orderby('p.ID')
	->orderby('t.term_id')
	;
	
	pbug( $qb->render()->query() );
 *
 */
class WP_QueryBuilder {
	/**
	 * Query builder - internal storage for aspects of query
	 * @var array
	 */
	private $_querray;
	
	/**
	 * Private storage for rendered query string
	 * @var string
	 */
	private $_queryString;
	
	/**
	 * Return the rendered query string
	 */
	public function query(){
		return $this->_queryString;
	}
	
	/**
	 * Return the query "Object"
	 */
	public function queryObject(){
		return $this->_querray;
	}
	
	#region ------------------- CONSTRUCTOR/DESTRUCTOR -----------------------
	
	public function __construct(){
		$this->_querray = array();
	}
	/**
	 * Legacy constructor
	 */
	public function WPQueryBuilder(){
		$this->__construct();
	}
	
	/**
	 * Clears the stored query, or the indicated aspect
	 * @param string $aspect - what part to clear - where, select, from, etc
	 */
	public function clear($aspect = false){
		//remove everything if no clause is specified
		if($aspect === false){
			unset($this->_querray);
			$this->_querray = array();
			return $this;
		}
		
		unset($this->_querray[$aspect]);
		$this->_querray = array();
		return $this;
	}
	
	#endregion ------------------- CONSTRUCTOR/DESTRUCTOR -----------------------
	
	
	#region ------------------- CLAUSES & CONDITIONALS -----------------------
	
	/**
	 * Remove an aspect from the query array
	 * @param string $aspect what part of the query to remove (select, where, etc)
	 * @param int $index (OPTIONAL) the numeric index into the query array of the specific clause to remove
	 */
	public function unclause($aspect, $index = NULL){
		if(isset( $this->_querray[$aspect] )){
			if($index !== NULL){
				unset( $this->_querray[$aspect][$index] );
				return $this;
			}
			
			unset( $this->_querray[$aspect] );
		}
		
		return $this;
	}
	
	/**
	 * Helper method to add a clause to an aspect of the query
	 * @param string $clause
	 * @param string $aspect - like 'select', 'from', 'where', etc
	 */
	private function clause($clause, $aspect){
		if( !isset( $this->_querray[$aspect] ) ) {
			$this->_querray[$aspect] = array();
		}
		
		$this->_querray[$aspect][] = $clause;
		return $this;
		
	}
	/**
	 * Helper method to add a clause to an aspect of the query
	 * @param string $clause
	 * @param string $aspect - like 'select', 'from', 'where', etc
	 * @param string $key - a specific key to attach within the aspect
	 */
	private function clauseFor($clause, $aspect, $key){
		//make a bucket if DNE
		if( !isset( $this->_querray[$aspect] ) ) {
			$this->_querray[$aspect] = array();
		}
		else if( !isset( $this->_querray[$aspect][$key] ) ) {
			$this->_querray[$aspect][$key] = array();
		}
		
		$this->_querray[$aspect][$key][] = $clause;
		return $this;
		
	}
	
	#region ------------------- BASIC CLAUSES -----------------------
	
	/**
	 * Chain a clause
	 * @param string $clause
	 */
	public function select($clause){
		return $this->clause($clause, 'select');
	}
	
	/**
	 * Chain a clause
	 * @param string $table
	 */
	public function from($table){
		return $this->clause($table, 'from');
	}
	
	/**
	 * Chain a clause
	 * @param string $clause what to order by
	 * @param string $sort how to order it -- DEFAULT ASC
	 */
	public function orderby($clause, $sort = false){
		return $this->clause($clause. ' ' . $sort, 'orderby');
	}
	
	/**
	 * Chain a join clause
	 * @param string $table what table to join to
	 * @param string $clause the join conditional
	 * @param string $type how to join it -- DEFAULT = INNER
	 */
	public function join($table, $clause, $type = 'INNER'){
		return $this->clause(array(
			'table'=>$table
			,'clause'=>$clause
			,'type'=>$type
		), 'join');
	}
	
	/**
	 * Chain a limit clause
	 * @param int $limit only get X results
	 * @param int $offset skip Y results
	 */
	public function limit($limit, $offset = NULL){
		if(!is_numeric($limit)){ throw new Exception('Error building SQL - non-numeric limit'); }
		
		if(isset($offset)){
			if(!is_numeric($offset)){ throw new Exception('Error building SQL - non-numeric offset'); }
			
			$limit = "$offset, $limit";
		}
		return $this->clause($limit, 'limit');
	}
	
	/**
	 * Chain a clause -- {$how} {$what} LIKE {$isLike}
	 * @param string $what the thing to check
	 * @param string $isLike what the $what is like
	 * @param string $how how to append this clause
	 */
	public function like($what, $isLike, $how = 'AND'){
		return $this->clause(array('clause'=>sprintf('%s LIKE \'%s\'', $what, $isLike),'how'=>$how), 'where');
	}
	
	/**
	 * Chain a clause
	 * @param mixed $clause where condition; if given as an array, first index is query, second is $how (AND or OR)
	 * @param variable $params a list of values to use in ->prepare
	 */
	public function where($clause = NULL){
		if( NULL === $clause ) throw new WP_QueryBuilderException('Must specify the clause', WP_QueryBuilderException::EMPTY_ARGUMENTS);
		
		//process the rest of the variables
		$args = func_get_args();
		array_shift($args);	//pop the clause
		
		//if given as an array, first index is query, second is $how (AND or OR) 
		if( is_array($clause) ){
			$where_clause = $clause[0];
			$how = v($clause['how'], 'AND');
			$sanitize = v($clause['sanitize'], false);
			
			//remove defined params; anything left will later be used as $args
			unset($clause[0]);
			unset($clause['sanitize']);
			unset($clause['how']);
			
			//any leftovers are the new args
			if( empty($args) && !empty($clause) ){
				$args = $clause;
			}
		}
		else {
			$how = 'AND';
			$where_clause = $clause;
		}
		
		// if we're given a bunch of parameters, use prepare instead
		if( !empty($args) ){
			//add sanitize back on, if given
			if( isset($sanitize) && false !== $sanitize ) {
				array_unshift($args, 'sanitize', $sanitize);
			}
			//add the clause back on to the list, for call_user_func_array
			array_unshift($args, $where_clause);
			$clause = call_user_func_array( array(&$this, 'prepare'), $args);
		}
		
		return $this->clause(array('clause'=>$clause,'how'=>$how), 'where');
	}
	
	#endregion ------------------- BASIC CLAUSES -----------------------
	
	/**
	 * Chain a clause - add a nested where clause, like WHERE ( nested1 AND nested2 OR nested3 ) AND ...
	 * @param string $clauseId the specific nested grouping
	 * @param string $clause where condition
	 * @param string $how how to append this clause
	 */
	public function nested_where($clauseId, $clause, $how = 'AND'){
		return $this->clauseFor(array('clause'=>$clause,'how'=>$how), 'nested_where', $clauseId);
	}
	/**
	 * Chain a clause - add a nested where clause, like WHERE ( nested1 AND nested2 OR nested3 ) AND ...
	 * @param string $clauseId the specific nested grouping
	 * @param string $how how to append this clause
	 */
	public function start_nested_where($clauseId, $how = 'AND'){
		return $this->clauseFor($how, 'nested_where_ids', $clauseId);
	}	
	
	
	#endregion ------------------- CLAUSES & CONDITIONALS -----------------------
	
	/**
	 * Build and return the query string
	 */
	public function render(){
		$select = implode(' , ', $this->_querray['select']);
		$from = implode(' , ', $this->_querray['from']);
		
		//start the query with the basics that'll be there
		$this->_queryString = sprintf("SELECT %s \nFROM %s \n"
			, $select
			, $from
		);
		
		//now add the rest
		
		//joins
		if(isset($this->_querray['join'])):
		foreach($this->_querray['join'] as $join){
			$this->_queryString = sprintf("%s \n    %s JOIN %s ON (%s) "
				, $this->_queryString
				, $join['type']
				, $join['table']
				, $join['clause']
			);
		}
		endif;	//join
		
		//where
		if(isset($this->_querray['where']) && !empty($this->_querray['where'])):
			$where = "\nWHERE ";
			
			//handle first one differently
			$i = 0;
			foreach($this->_querray['where'] as $w){
				if($i++ == 0){
					$where .= sprintf("\n   (%s) ",$w['clause']);
				}
				else{
					$where .= sprintf(" \n   %s (%s) ",$w['how'],$w['clause']);
				}
			}
			
			$this->_queryString .= $where;
		endif;	//where
		
		//nested wheres
		if(isset($this->_querray['nested_where']) && !empty($this->_querray['nested_where'])):
			//start a new where clause if DNE
			if(empty($where)){ $where = "\nWHERE "; $j = 0; }
			else { $where = ''; $j = 1; }
			
			foreach($this->_querray['nested_where'] as $nestedId => $nesting){
				//handle first one differently
				if($j++ == 0){
					$where .= "\n	(";
				}
				else{
					//get the how from the nested where id list
					if(isset($this->_querray['nested_where_ids'][$nestedId])){
						$how = $this->_querray['nested_where_ids'][$nestedId][0];
					}
					//otherwise, default to AND
					else{
						$how = 'AND';
					}
					
					$where .= "\n $how	(";
				}
				
				//now handle the actual nesting
				$i = 0;
				foreach($nesting as $w){
					//handle first one differently
					if($i++ == 0){
						$where .= sprintf("\n   (%s) ",$w['clause']);
					}
					else{
						$where .= sprintf(" \n   %s (%s) ", $w['how'],$w['clause']);
					}
				}
				
				$where .= ' ) ';
			}
			
			$this->_queryString .= $where;
		endif;	//nested wheres
		
		
		//orderby
		if(isset($this->_querray['orderby']) && !empty($this->_querray['orderby'])):
			$this->_queryString = sprintf("%s \nORDER BY %s "
				, $this->_queryString
				, implode(' , ', $this->_querray['orderby'])
			); 
		endif;	//orderby
		
		//orderby
		if(isset($this->_querray['limit']) && !empty($this->_querray['limit'])):
			$this->_queryString = sprintf("%s \nLIMIT %s "
				, $this->_queryString
				, implode(' , ', $this->_querray['limit'])
			); 
		endif;	//orderby

		//groupby
		///TODO
		
		//chain, to get ->query later
		return $this;
	}//----	end function render
	
	
	#region ------------------- STATIC STUFF -----------------------
	
	/**
	 * Create a clause where $something ($condition) $somethingelse, like $A = $B
	 * With sanitizing
	 * !!! DEPRECATED in favor of prepare !!!
	 * @param string $something the first thing to compare
	 * @param string $relationship -- LIKE or = or <
	 * @param string $somethingelse the second thing to compare
	 * @param string $parseAs - OPTIONAL - how to parse the $somethingelse value - as 'value',...
	 */
	static function CONDITION(){
		$args = func_get_args();
		
		//optional forced sanitization
		if(count($args) > 1 && $args[0] === 'sanitize'):
			//remove sanitize arg
			array_shift($args);
			
			$sanitize_pattern = '/[^\w\s-=><%._]/';
			foreach($args as $arg){
				if(preg_match($sanitize_pattern, $arg) > 0){
					$error = 'Error creating CLAUSE -- invalid characters: '.$arg;
					throw new Exception($error);
				}
			}
		endif;	//end sanitize check
		
		
		//if we have additional parameters, parse accordingly
		if(count($args) > 3){
			//somethingelse is a value type - escape it in quotes
			if($args[3] == 'value'){
				return sprintf("%s %s '%s'", $args[0], $args[1], addslashes($args[2]));
			}
			else{
				return sprintf("%s %s %s", $args[0], $args[1], $args[2]);
			}
		}
		else{
			return sprintf("%s %s %s", $args[0], $args[1], $args[2]);
		}
	}
	
	/**
	 * Wrapper for core prepare statement
	 * @param $sanitize {optional} if given as TRUE or 'sanitize', will force preg_match sanitization of args; otherwise, first param is $query
	 * @param $pattern {optional} if the first parameter is given as TRUE or "sanitize", enter the regex pattern here, or "null" to use default 
	 * @param $query give the query
	 * @param $args, give the rest of the arguments to prepare
	 */
	public function prepare(){
		global $wpdb;	//needed for core prepare
		$args = func_get_args();
		$query = array_shift($args);
		
		//forced sanitization
		if( true === $query || 'sanitize' === $query ):
			$sanitize_pattern  = array_shift($args);	//next param is the pattern
			//but if they want to use the default, it'll be given as null
			if( null === $sanitize_pattern ) {
				$sanitize_pattern = '/[^\w\s-_.%]/';
			}
			$query = array_shift($args);	//next param is the actual query
			
			$sanitize_pattern = '/[^\w\s-_.%]/';
			foreach($args as $arg){
				if(preg_match($sanitize_pattern, $arg) > 0){
					$error = 'Error preparing CLAUSE -- invalid characters: '.$arg;
					throw new WP_QueryBuilderException($error, WP_QueryBuilderException::SANITIZE);
				}
			}//	foreach
		endif;	// forced sanitization
		
		return $wpdb->prepare($query, $args);
	}//--	fn	prepare
	
	#endregion ------------------- STATIC STUFF -----------------------
	
}///---	class	WP_QueryBuilder


wp_library_include('customexception.class.php');
/**
 * Associated error class
 * @author jeremys
 *
 */
class WP_QueryBuilderException extends CustomException {
	/**
	 * Error Codes
	 * @var int
	 */
	const GENERAL = 0
		, SANITIZE = 1
		, EMPTY_ARGUMENTS = 2
		;
	
		
	public function __construct($message = null, $code = 0)
	{
		if (!$message) {
			throw new $this('Unknown '. get_class($this));
		}
		
		switch($code){
			case self::GENERAL:
				$prefix = '[General] ';
				break;
			case self::SANITIZE:
				$prefix = '[Unsanitized] ';
				break;
			case self::EMPTY_ARGUMENTS:
				$prefix = '[Empty Arguments] ';
				break;
			default:
				break;
		}//	switch $code
		
		parent::__construct($prefix.$message, $code);
	}//--	fn	__construct
	
}///---	class	WP_QueryBuilderException


?>
