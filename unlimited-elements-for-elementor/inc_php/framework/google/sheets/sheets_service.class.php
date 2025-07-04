<?php

/**
 * @link https://developers.google.com/sheets/api/reference/rest
 */
class UEGoogleAPISheetsService extends UEGoogleAPIClient{

	/**
	 * Get the spreadsheet.
	 *
	 * @param string $spreadsheetId
	 * @param array $params
	 *
	 * @return UEGoogleAPISpreadsheet
	 */
	public function getSpreadsheet($spreadsheetId, $params = array()){

		$response = $this->get("/$spreadsheetId", $params);
		$response = UEGoogleAPISpreadsheet::transform($response);

		return $response;
	}

	/**
	 * Get the spreadsheet values.
	 *
	 * @param string $spreadsheetId
	 * @param string $range
	 * @param array $params
	 *
	 * @return UEGoogleAPISheetValues
	 */
	public function getSpreadsheetValues($spreadsheetId, $range, $params = array()){

		$range = urlencode($range);

		$response = $this->get("/$spreadsheetId/values/$range", $params);
		$response = UEGoogleAPISheetValues::transform($response);

		return $response;
	}


	/**
	 * Get the spreadsheet values with links.
	 *
	 * @param string $spreadsheetId
	 * @param string $range
	 * @param array $params
	 *
	 * @return UEGoogleAPISheetValues
	 */
	public function getSpreadsheetValuesWithLinksAndAttributes($spreadsheetId, $ranges = array(), $params = array()) {
		$params["fields"] = 'sheets.data.rowData.values(effectiveFormat.textFormat,textFormatRuns,formattedValue,hyperlink)';
		if (!empty($ranges))
			$params["ranges"] = $ranges;

		$response = $this->get("/$spreadsheetId", $params);
		$response = UEGoogleAPISheetValues::transform($response);

		return $response;
	}


	/**
	 * Batch update the spreadsheet.
	 *
	 * @param string $spreadsheetId
	 * @param array $requests
	 *
	 * @return void
	 */
	public function batchUpdateSpreadsheet($spreadsheetId, $requests){

		$this->post("/$spreadsheetId:batchUpdate", array(
			"requests" => $requests,
		));
	}

	/**
	 * Get the insert dimension request.
	 *
	 * @param int $sheetId
	 * @param int $startIndex
	 * @param int $endIndex
	 *
	 * @return array
	 */
	public function getInsertDimensionRequest($sheetId, $startIndex, $endIndex){

		$request = array(
			"insertDimension" => array(
				"range" => array(
					"sheetId" => $sheetId,
					"startIndex" => $startIndex,
					"endIndex" => $endIndex,
					"dimension" => "ROWS",
				),
			),
		);

		return $request;
	}


	/**
	 * Get the update cells request.
	 *
	 * @param int $sheetId
	 * @param int $startIndex
	 * @param int $endIndex
	 * @param array $rows
	 *
	 * @return array
	 */
	public function getUpdateCellsRequest($sheetId, $startIndex, $endIndex, $rows){

		$columnsCount = isset($rows[0]['values']) ? count($rows[0]['values']) : 0;

		$request = array(
			"updateCells" => array(
				"range" => array(
					"sheetId" => $sheetId,
					"startRowIndex" => $startIndex,
					"endRowIndex" => $endIndex,
					"startColumnIndex" => 0,
					"endColumnIndex" => $columnsCount
				),
				"rows" => $rows,
				"fields" => "*",
			),
		);


		return $request;
	}

	/**
	 * Prepare the row data.
	 *
	 * @param array $values
	 *
	 * @return array
	 */
	public function prepareRowData($values){

		$data = array(
			"values" => $values,
		);

		return $data;
	}

	/**
	 * Prepare the cell data.
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	public function prepareCellData($value){

		if(is_numeric($value) === true)
			$type = "numberValue";
		elseif(is_bool($value) === true)
			$type = "boolValue";
		else{
			$type = "stringValue";
			$value = (string)$value;
		}

		$data = array(
			"userEnteredValue" => array(
				$type => $value,
			),
		);

		return $data;
	}

	/**
	 * Apply the bold formatting for the cell.
	 *
	 * @param array $cell
	 *
	 * @return array
	 */
	public function applyBoldFormatting($cell){

		$cell["userEnteredFormat"] = array(
			"textFormat" => array(
				"bold" => true,
			),
		);

		return $cell;
	}

	/**
	 * Get the base URL for the API.
	 *
	 * @return string
	 */
	protected function getBaseUrl(){

		return "https://sheets.googleapis.com/v4/spreadsheets";
	}


	/**
	 * Get sheet properties.
	 *
	 * @param string $spreadsheetId
	 * @param int $sheetId
	 *
	 * @return array
	 */
	public function getSheetProperties($spreadsheetId, $sheetId) {
		$response = $this->get("/$spreadsheetId");

		foreach ($response['sheets'] as $sheet) {

			if ($sheet['properties']['sheetId'] == $sheetId) {
				return $sheet['properties']['gridProperties'];
			}
		}

		throw new Exception("Sheet properties not found.");
	}


	/**
	 * Batch update the spreadsheet.
	 *
	 * @param string $spreadsheetId
	 * @param array $requests
	 *
	 * @return array
	 */

	public function addColumnsIfNeeded( $spreadsheetId, $sheetId, $columnsToAdd ) {
		$request = array(
			'appendDimension' => array(
				'sheetId'   => $sheetId,
				'dimension' => 'COLUMNS',
				'length'    => $columnsToAdd,
			)
		);

		return $this->batchUpdateSpreadsheet( $spreadsheetId, $request );
	}

}
