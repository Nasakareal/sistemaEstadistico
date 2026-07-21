<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class C5iServiceResponse extends Model
{
    protected $table = 'c5i_service_responses';

    protected $fillable = [
        'whatsapp_web_group_id',
        'incident_message_id',
        'assignment_message_id',
        'arrival_message_id',
        'patrulla_id',
        'incident_reference',
        'incident_location',
        'incident_lat',
        'incident_lng',
        'reported_at',
        'assigned_at',
        'gps_arrived_at',
        'arrival_reported_at',
        'arrival_source',
        'report_to_gps_seconds',
        'assignment_to_gps_seconds',
        'arrival_message_delay_seconds',
        'gps_distance_meters',
        'gps_accuracy_meters',
        'status',
        'notification_status',
        'notification_meta',
        'notification_processed_at',
    ];

    protected $casts = [
        'incident_lat' => 'float',
        'incident_lng' => 'float',
        'reported_at' => 'datetime',
        'assigned_at' => 'datetime',
        'gps_arrived_at' => 'datetime',
        'arrival_reported_at' => 'datetime',
        'report_to_gps_seconds' => 'integer',
        'assignment_to_gps_seconds' => 'integer',
        'arrival_message_delay_seconds' => 'integer',
        'gps_distance_meters' => 'float',
        'gps_accuracy_meters' => 'float',
        'notification_meta' => 'array',
        'notification_processed_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(WhatsAppWebGroup::class, 'whatsapp_web_group_id');
    }

    public function incidentMessage()
    {
        return $this->belongsTo(WhatsAppWebMessage::class, 'incident_message_id');
    }

    public function assignmentMessage()
    {
        return $this->belongsTo(WhatsAppWebMessage::class, 'assignment_message_id');
    }

    public function arrivalMessage()
    {
        return $this->belongsTo(WhatsAppWebMessage::class, 'arrival_message_id');
    }

    public function patrulla()
    {
        return $this->belongsTo(Patrulla::class, 'patrulla_id');
    }
}
