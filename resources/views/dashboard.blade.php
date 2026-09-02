@extends('layout')

@section('content')

<h1 class="mb-4">Dashboard</h1>

<div class="row">

<div class="col-md-3">
<div class="card p-4 text-center">
<h2 id="scheduledCount">0</h2>
<h6>Scheduled</h6>
</div>
</div>

<div class="col-md-3">
<div class="card p-4 text-center">
<h2 id="confirmedCount">0</h2>
<h6>Confirmed</h6>
</div>
</div>

<div class="col-md-3">
<div class="card p-4 text-center">
<h2 id="completedCount">0</h2>
<h6>Completed</h6>
</div>
</div>

<div class="col-md-3">
<div class="card p-4 text-center">
<h2 id="cancelledCount">0</h2>
<h6>Cancelled</h6>
</div>
</div>

</div>

<div class="card mt-4">

<div class="card-body">

<h4>Recent Activity</h4>

<table class="table">

<thead>
<tr>
<th>ID</th>
<th>Address</th>
<th>Status</th>
</tr>
</thead>

<tbody id="recentTable"></tbody>

</table>

</div>

</div>

<script>

let pickups =
JSON.parse(localStorage.getItem("pickups")) || [];

scheduledCount.innerText =
pickups.filter(x=>x.status=="Scheduled").length;

confirmedCount.innerText =
pickups.filter(x=>x.status=="Confirmed").length;

completedCount.innerText =
pickups.filter(x=>x.status=="Completed").length;

cancelledCount.innerText =
pickups.filter(x=>x.status=="Cancelled").length;

let html='';

pickups.forEach(p=>{

html += `
<tr>
<td>${p.id}</td>
<td>${p.address}</td>
<td>${p.status}</td>
</tr>
`;

});

recentTable.innerHTML = html;

</script>

@endsection