<?php

namespace TOT;

class Reasons {

	public $reasons = null;

	public function __construct( $reasons ) {
		$this->reasons = $reasons;
	}

	public function is_positive( $key ) {
        if( !$this->reason_has_value($key) ) {
            return false;
        } else if( ($this->reasons->$key === 'partialMatch') || ($this->reasons->$key === 'fullMatch') ) {
            return true;
        } else if (!isset($this->reasons->$key->value)) {
            return false;
        } else if (($this->reasons->$key->value === 'partialMatch') || ($this->reasons->$key->value === 'fullMatch')) {
            return true;
        } else {
            return false;
        }
    }

	public function is_negative( $key ) {

		if( !$this->reason_has_value($key) ) {
			return false;
		} else if ($this->reasons->$key === 'noMatch') {
		    return true;
		} else if (!isset($this->reasons->$key->value)) {
		    return false;
		} else if ($this->reasons->$key->value === 'partialMatch') {
		    return true;
		} else {
		    return false;
		}
	}

	public function has_insufficient_data( $key ) {

		if( !$this->reason_has_value($key) ) {
			return false;
		}

		if( ($this->reasons->$key === 'insufficientData') || ($this->reasons->$key === 'insufficientData') ) {
			return true;
		}

		if( ($this->reasons->$key->value === 'insufficientData') || ($this->reasons->$key->value === 'insufficientData') ) {
			return true;
		}

		return false;

	}

	public function are_empty() {

		if( !isset($this->reasons) ) {
			return true;
		}

		if( \is_wp_error($this->reasons) ) {
			tot_display_error($this->reasons);
			return true;
		}

		return empty((array) $this->reasons);
	}

	public function reason_has_value( $key ) {

		if( $this->are_empty() ) {
			return false;
		}

		if( !isset( $this->reasons->$key ) ) {
			return false;
		}

		if( is_object( $this->reasons->$key ) ) {
			$as_array = (array) $this->reasons->$key;
			if( empty($as_array) ) {
				return false;
			}
		}

		return true;

	}

	public function reason_details( $key ) {

		if( $this->are_empty() ) {
			return null;
		}

		if( !isset( $this->reasons->$key ) ) {
			return null;
		}

		return $this->reasons->$key;

	}

}