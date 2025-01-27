<?php

namespace LeadBrowser\Core\Repositories;

use LeadBrowser\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Storage;
use Prettus\Repository\Traits\CacheableRepository;

class CoreConfigRepository extends Repository
{
    use CacheableRepository;

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'LeadBrowser\Core\Contracts\CoreConfig';
    }

    /**
     * @param  array  $data
     * @return \LeadBrowser\Core\Contracts\CoreConfig
     */
    public function create(array $data)
    {
        unset($data['_token']);

        foreach ($data as $method => $fieldData) {
            $recurssiveData = $this->recuressiveArray($fieldData , $method);

            foreach ($recurssiveData as $fieldName => $value) {
                $field = core()->getConfigField($fieldName);

                if (getType($value) == 'array' && ! isset($value['delete'])) {
                    $value = implode(",", $value);
                }

                $coreConfigValue = $this->model
                                    ->where('code', $fieldName)
                                    ->get();

                if (request()->hasFile($fieldName)) {
                    $value = request()->file($fieldName)->store('configuration');
                }

                if (! count($coreConfigValue)) {
                    $this->model->create([
                        'code'         => $fieldName,
                        'value'        => $value,
                    ]);
                } else {
                    foreach ($coreConfigValue as $coreConfig) {
                        Storage::delete($coreConfig['value']);

                        if(isset($value['delete'])) {
                            $this->model->destroy($coreConfig['id']);
                        } else {
                            $coreConfig->update([
                                'code'         => $fieldName,
                                'value'        => $value,
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  array  $formData
     * @param  string  $method
     * @return array
     */
    public function recuressiveArray(array $formData, $method) {
        static $data = [];

        static $recuressiveArrayData = [];

        foreach ($formData as $form => $formValue) {
            $value = $method . '.' . $form;

            if (is_array($formValue)) {
                $dim = $this->countDim($formValue);

                if ($dim > 1) {
                    $this->recuressiveArray($formValue, $value);
                } elseif ($dim == 1) {
                    $data[$value] = $formValue;
                }
            }
        }

        foreach ($data as $key => $value) {
            $field = core()->getConfigField($key);

            if ($field) {
                $recuressiveArrayData[$key] = $value;
            } else {
                foreach ($value as $key1 => $val) {
                    $recuressiveArrayData[$key . '.' . $key1] = $val;
                }
            }
        }

        return $recuressiveArrayData;
    }

    /**
     * Return dimension of array
     *
     * @param  array  $array
     * @return int
    */
    public function countDim($array)
    {
        if (is_array(reset($array))) {
            $return = $this->countDim(reset($array)) + 1;
        } else {
            $return = 1;
        }

        return $return;
    }
}
