@extends('layout')

@section('content')

<h1 class="mb-4">Recipient Pickup Scheduling</h1>

<div class="card">

<div class="card-body">

<div class="row">

<div class="col-md-6">

<label>Match ID</label>
<input id="matchId" class="form-control mb-3">

<label>Pickup Address</label>
<input id="address" class="form-control mb-3">

<label>Date & Time</label>
<input type="datetime-local" id="pickupDate" class="form-control mb-3">

<button onclick="schedulePickup()" class="btn btn-success">
Schedule Pickup
</button>

</div>

</div>

</div>

</div>

<script>

function schedulePickup(){

let pickups =
JSON.parse(localStorage.getItem("pickups")) || [];

pickups.push({

id: Date.now(),

matchId:
document.getElementById("matchId").value,

address:
document.getElementById("address").value,

datetime:
document.getElementById("pickupDate").value,

status:"Scheduled"

});

localStorage.setItem(
"pickups",
JSON.stringify(pickups)
);

alert("Pickup Scheduled Successfully");

}

</script>

@endsection