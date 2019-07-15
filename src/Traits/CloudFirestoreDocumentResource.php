<?php

namespace Wead\Firestore\Traits;

trait CloudFirestoreDocumentResource
{
    abstract public function getDocument();

    private function document($collection, $name, $createDefaultDoc = false)
    {
        $collection->name = substr($collection->name, -1) == '/' ? substr($collection->name, 0, -1) : $collection->name;

        $name = self::clearName($name);

        $uri = "{$collection->name}/{$name}";

        if (!$createDefaultDoc) {
            $response = new \stdClass;
            $response->name = $name;
            $response->fullName = $uri;
        } else {
            $response = $this->makeRequestApi('POST', $uri);
        }

        $response->objectType = "document";

        return $response;
    }
    
    private function updateDocument($doc, $fields = [])
    {
        $doc->name = substr($doc->name, -1) == '/' ? substr($doc->name, 0, -1) : $doc->name;
        $doc->fullName = substr($doc->fullName, -1) == '/' ? substr($doc->fullName, 0, -1) : $doc->fullName;

        $doc->name = self::clearName($doc->name);

        $uri = $this->getBaseUri($doc->fullName);

        $mappedFields = self::map($fields);
        $fieldsMapped = [
                     "name" => $doc->name,
                     "fields" => $mappedFields["fields"],
                 ];


        $response = $this->makeRequestApi('PATCH', $uri, $fieldsMapped);
        $response->fullName = $response->name;

        $response->objectType = "document";
        $response->name = str_replace($this->getBaseUri(), '', $uri);

        $response->name = self::clearName($response->name);

        return $response;
    }

    public static function map($fields){
        $out = ['fields' => []];
        foreach ($fields as $field => $value) {
            $out["fields"][$field] = self::mapValues($value);

            if (!$out["fields"][$field]) {
                unset($out["fields"][$field]);
            }
        }
        return $out;
    }

    public static function isAssoc(array $arr)
        {
            if (array() === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        }

    public static function mapValues($value){

        if (is_object($value)) {
            if (!$value instanceof \Carbon\Carbon) {
                throw new \Exception("Unknown object type");
            }

            $result = "carbonTimestampField";
        } else if ((filter_var($value, FILTER_VALIDATE_INT, ['min_range' => 0]) != false || $value == "0" ) && substr_count($value, ".") == 0) {
            return ["integerValue" => trim($value)];
        } else if (is_numeric($value) && substr_count($value, ".") > 0 && preg_match('/[^0-9.]/', $value) == 0 && str_split($value)[0] != "0") {
            return ["doubleValue" => trim($value)];
        } else if (is_string($value)) {
            return ["stringValue" => (string) trim($value)];
        } else if (is_null($value)) {
            return ["nullValue" => null];
        } else if (is_bool($value)) {
            return ["booleanValue" => (bool) trim($value)];
        } else if (is_array($value)) {
            $k = self::map($value);
            return self::isAssoc($value) ? ["mapValue" => $k] :
            ["arrayValue" => ["values" => $k["fields"]]];
        }
    }

    private function readDocumentFields($doc)
    {
        $doc->name = substr($doc->name, -1) == '/' ? substr($doc->name, 0, -1) : $doc->name;
        $doc->fullName = substr($doc->fullName, -1) == '/' ? substr($doc->fullName, 0, -1) : $doc->fullName;

        $doc->name = self::clearName($doc->name);

        $uri = $this->getBaseUri($doc->fullName);

        $response = $this->makeRequestApi('GET', $uri);
        $response->fullName = $response->name;

        $response->objectType = "document";
        $response->name = str_replace($this->getBaseUri(), '', $uri);

        return $response;
    }
}
