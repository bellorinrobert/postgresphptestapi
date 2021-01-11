<?php

class UrlPatternProcessor
{
    const GLOBAL_FOUND = 'global_found';
    const GLOBAL_NOT_FOUND = 'global_not_found';
    const NAME_FOUND = 'name_found';
    const NAME_NOT_FOUND = 'name_not_found';

    const LINE_DELIMITER = "\n";
    const GLOBAL_SEARCH_KEY = "_GLOBAL_SEARCH_KEY_";

    protected $userName;
    protected $formattedDataArray;
    protected $db;

    public function __construct($db, $ruleLabel, $userName = '')
    {
        $this->formattedDataArray = array();
        $this->db = $db;
        $this->userName = str_replace('@', '', $userName);

        $patternListIds = $this->getPatternListId($ruleLabel);

        foreach ($patternListIds as $listId) {
            $data = $this->loadDataFromDB($listId);
            $this->formattedDataArray[$listId] = $this->transformData($data);
        }
    }

    protected function getPatternListId($ruleLabel)
    {
        $query = "SELECT p.urlpattern_list_id
            FROM liv2_rules_to_urlpattern p
            INNER JOIN liv2_rules r ON r.rule_id = p.rule_id
            WHERE r.rule_remote_url like '%{$ruleLabel}' and r.rule_type='urlpattern_list'";

        $rows = $this->db->getAllAssoc($query);

        if (empty($rows)) {
            throw new \Exception('unable to find record in db: ' . $query);
        }

        $result = array();

        foreach ($rows as $row) {
            $result[] = (int) $row['urlpattern_list_id'];
        }

        return $result;
    }

    protected function loadDataFromDB($patternListId)
    {
        $query = "SELECT urlpattern_list_text 
            FROM liv2_rules_urlpattern_list 
            WHERE urlpattern_list_id = {$patternListId} 
            LIMIT 1";

        $row = $this->db->getARow($query);

        if (is_null($row)) {
            throw new \Exception('unable to find record in db: ' . $query);
        }

        if (empty($row['urlpattern_list_text'])) {
            throw new \Exception('empty data in urlpattern_list_text column');
        }

        return $row['urlpattern_list_text'];
    }

    protected function saveDataToDB($patternListId, $formattedData)
    {
        $value = $this->transformBackToString($formattedData);

        if (empty($value)) {
            throw new \Exception('unable to transformBackToString, result is empty');
        }

        $value = pg_escape_string($value);

        $query = "UPDATE liv2_rules_urlpattern_list 
            SET urlpattern_list_text = '{$value}'
            WHERE urlpattern_list_id = {$patternListId} ";

        $this->db->execQuery($query);
    }


    protected function transformBackToString($formattedData)
    {
        $result = array();

        if (array_key_exists(self::GLOBAL_SEARCH_KEY, $formattedData)) {
            $result[] = implode(self::LINE_DELIMITER, $formattedData[self::GLOBAL_SEARCH_KEY]);
        }

        foreach ($formattedData as $userName => $urlLines) {
            if ($userName == self::GLOBAL_SEARCH_KEY) {
                continue;
            }

            $oneRecord = '@' . $userName . self::LINE_DELIMITER;
            $oneRecord .= implode(self::LINE_DELIMITER, $urlLines);

            $result[] = $oneRecord;
        }

        return implode(self::LINE_DELIMITER, $result);
    }

    protected function transformData($data)
    {
        $isFirstLetterName = ($data[0] == '@');

        $exploded = explode('@', $$data);

        $result = array();

        foreach ($exploded as $i => $record) {
            $record = trim($record);

            if (empty($record)) {
                continue;
            }

            $explodedRecord = explode(self::LINE_DELIMITER, $record);

            if ($i == 0 and !$isFirstLetterName) {
                $result[self::GLOBAL_SEARCH_KEY] = $explodedRecord;
            } else {
                $result[array_shift($explodedRecord)] = array_map('trim', $explodedRecord);
            }
        }

        return $result;
    }

    private function getSearchStatus($formattedData)
    {
        if (empty($this->userName)) {
            return array_key_exists(self::GLOBAL_SEARCH_KEY, $formattedData)
                ? self::GLOBAL_FOUND
                : self::GLOBAL_NOT_FOUND;
        }

        return array_key_exists($this->userName, $formattedData)
            ? self::NAME_FOUND
            : self::NAME_NOT_FOUND;
    }

    private function isUrlExists($source, $text)
    {
        return in_array($text, $source);
    }

    public function insert($text)
    {
        foreach ($this->formattedDataArray as $patternListId => $formattedData) {
            switch($this->getSearchStatus($formattedData)) {
                case self::GLOBAL_FOUND:
                    if ($this->isUrlExists($formattedData[self::GLOBAL_SEARCH_KEY], $text)) {
                        break;
                    }

                    $formattedData[self::GLOBAL_SEARCH_KEY][] = $text;
                    break;
                case self::GLOBAL_NOT_FOUND:
                    $formattedData[self::GLOBAL_SEARCH_KEY] = array($text);
                    break;
                case self::NAME_FOUND:
                    if ($this->isUrlExists($formattedData[$this->userName], $text)) {
                        break;
                    }

                    $formattedData[$this->userName][] = $text;
                    break;
                case self::NAME_NOT_FOUND:
                    $formattedData[$this->userName][] = $text;
                    break;
                default:
                    throw new \Exception('Unable to getSearchStatus');
                    break;
            }

            $this->saveDataToDB($patternListId, $formattedData);
        }
    }

    public function update($oldText, $newText)
    {
        foreach ($this->formattedDataArray as $patternListId => $formattedData) {
            switch ($this->getSearchStatus($formattedData)) {
                case self::GLOBAL_FOUND:
                    $index = array_search($oldText, $formattedData[self::GLOBAL_SEARCH_KEY]);

                    if ($index !== false) {
                        $formattedData[self::GLOBAL_SEARCH_KEY][$index] = $newText;
                    }
                    break;
                case self::NAME_FOUND:
                    $index = array_search($oldText, $formattedData[$this->userName]);

                    if ($index !== false) {
                        $formattedData[$this->userName][$index] = $newText;
                    }
                    break;
                case self::GLOBAL_NOT_FOUND:
                    break;
                case self::NAME_NOT_FOUND:
                    break;
                default:
                    throw new \Exception('Unable to getSearchStatus');
                    break;
            }

            $this->saveDataToDB($patternListId, $formattedData);
        }
    }

    public function remove($text)
    {
        foreach ($this->formattedDataArray as $patternListId => $formattedData) {
            switch ($this->getSearchStatus($formattedData)) {
                case self::GLOBAL_FOUND:
                    $index = array_search($text, $formattedData[self::GLOBAL_SEARCH_KEY]);

                    if ($index !== false) {
                        unset($formattedData[self::GLOBAL_SEARCH_KEY][$index]);
                    }

                    if (count($formattedData[self::GLOBAL_SEARCH_KEY]) == 0) {
                        unset($formattedData[self::GLOBAL_SEARCH_KEY]);
                    }
                    break;
                case self::NAME_FOUND:
                    $index = array_search($text, $formattedData[$this->userName]);

                    if ($index !== false) {
                        unset($formattedData[$this->userName][$index]);
                    }

                    if (count($formattedData[$this->userName]) == 0) {
                        unset($formattedData[$this->userName]);
                    }
                    break;
                case self::GLOBAL_NOT_FOUND:
                    break;
                case self::NAME_NOT_FOUND:
                    break;
                default:
                    throw new \Exception('Unable to getSearchStatus');
                    break;
            }

            $this->saveDataToDB($patternListId, $formattedData);
        }
    }
}
