<?php

include_once(dirname(__FILE__)."/../functions/global.php");

$aColumns = array( 'rownum', 'username', 'nickname', 'total_ac', 'total_submit' );
$sIndexColumn = "uid";
$sTable = "(
    SELECT @rownum := @rownum +1 rownum, ranklist . * 
    FROM (
        SELECT @rownum :=0
    )r, ranklist
) AS t";

//paging
$sLimit = "";
if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
{
    $sLimit = "LIMIT ".convert_str( $_GET['iDisplayStart'] ).", ".
        convert_str( $_GET['iDisplayLength'] );
}

//ordering
if ( isset( $_GET['iSortCol_0'] ) )
{
    $sOrder = "ORDER BY  ";
    for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
    {
        if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
        {
            $sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
                ".convert_str( $_GET['sSortDir_'.$i] ) .", ";
        }
    }
    
    $sOrder = substr_replace( $sOrder, "", -2 );
    if ( $sOrder == "ORDER BY" )
    {
        $sOrder = "";
    }
}

//filtering
$sWhere = "";
if ( $_GET['sSearch'] != "" )
{
    $sWhere = "WHERE (";
    for ( $i=1 ; $i<3 ; $i++ )
    {
        $sWhere .= $aColumns[$i]." LIKE '%".convert_str( $_GET['sSearch'] )."%' OR ";
    }
    $sWhere = substr_replace( $sWhere, "", -3 );
    $sWhere .= ')';
}

/* Individual column filtering */
for ( $i=0 ; $i<count($aColumns) ; $i++ )
{
    if ( $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
    {
        if ( $sWhere == "" )
        {
            $sWhere = "WHERE ";
        }
        else
        {
            $sWhere .= " AND ";
        }
        $sWhere .= $aColumns[$i]." LIKE '%".convert_str($_GET['sSearch_'.$i])."%' ";
    }
}

/* Total data set length */
$sQuery = "
    SELECT COUNT(".$sIndexColumn.")
    FROM   $sTable
";
$aResultTotal = $db->get_row($sQuery,ARRAY_N);
$iTotal = $aResultTotal[0];
if ($EZSQL_ERROR) die("SQL Error!");

/*
 * SQL queries
 * Get data to display
 */
$sQuery = "
    SELECT ".str_replace(" , ", " ", implode(", ", $aColumns))."
    FROM   $sTable
    $sWhere
    $sOrder
";
$db->query($sQuery);
$iFilteredTotal=$db->num_rows;

/*
 * Output
 */
$output = array(
    "sEcho" => intval($_GET['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => array()
);

$sQuery = "
    SELECT ".str_replace(" , ", " ", implode(", ", $aColumns))."
    FROM   $sTable
    $sWhere
    $sOrder
    $sLimit
";


foreach ( (array)$db->get_results( $sQuery,ARRAY_A ) as $aRow )
{
    $row = array();
    for ( $i=0 ; $i<count($aColumns) ; $i++ )
    {
        if ($aColumns[$i] == "nickname" ) {
            $row[] = change_out_nick($aRow[ $aColumns[$i] ]);
        }
        else if ( $aColumns[$i] != ' ' )
        {
            /* General output */
            $row[] = $aRow[ $aColumns[$i] ];
        }
    }
    $output['aaData'][] = $row;
}

echo json_encode( $output );

?>
