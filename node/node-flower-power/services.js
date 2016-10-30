module.exports = {
  live: {
    uuid: 'fa00',
    characteristic: [
      'light_sensor_value',
      'soil_ec',
      'soil_temperature',
      'air_temperature',
      'soil_percent_vwc',
      'live_measure_period',
      'led_state',
      '',
      'calibrate_vwc',
      'calibrate_air_temperature',
      'calibrate_dli',
      '',
      '',
      '',
      'light_red_sensor_value',
      'light_green_sensor_value',
      'light_blue_sensor_value'
    ]
  },
  upload: {
    uuid: 'fb00',
    characteristic: [
      'tx_buffer',
      'tx_status',
      'rx_status'
    ]
  },
  history: {
    uuid: 'fc00',
    characteristic: [
      'nb_entries',
      'last_entry_index',
      'transfer_start_index',
      'current_session_id',
      'current_session_start_index',
      'current_session_period'
    ]
  },
  clock: {
    uuid: 'fd00',
    characteristic: [
      'hawaii_current_time',
      'utc_time_since_epoch'
    ]
  },
  watering: {
    uuid: 'f900',
    characteristic: [
      'config_id',
      'plant_id',
      'vwc_irr',
      'vwc_cmd',
      'n_irr',
      'watering_cmd',
      'water_tank_level',
      'pump_duty_cycle',
      '',
      'vwc_irr_eco',
      'vwc_cmd_eco',
      'n_irr_eco',
      'watering_mode',
      'watering_time_slot_start',
      'watering_time_slot_duration',
      'vacation_start',
      'vacation_end',
      'watering_algorithm_status'
    ]
  },
  plant_doctor: {
    uuid: 'fd80',
    characteristic: [
      'config_id',
      'dry_n',
      'dry_vwc',
      'wet_n',
      'wet_vwc',
      'status_flags',
      'next_watering_date',
      'next_empty_tank_date',
      'full_tank_autonomy'
    ]
  },
  configuration: {
    uuid: 'fe00',
    characteristic: [
      'calibration_data',
      '',
      'name',
      'color',
      'tank_capacity',
      'is_available'
    ]
  }
};
