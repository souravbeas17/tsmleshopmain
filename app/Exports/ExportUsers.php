<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportUsers implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $getThresholdData;
    function __construct($getThresholdData) 
	{
		// dd($getThresholdData);
	    $this->getThresholdData = $getThresholdData;
	} 
    public function collection()
    {
    	// dd($this->getThresholdData);
        return collect($this->getThresholdData);
    }

    public function headings(): array
    {
        return [
        	'Name',
        	'Email',
            'UserId',
            'Organistation Name', 
            'User Type',
            'Zone',
            'Created At'            
        ];
    }
}
