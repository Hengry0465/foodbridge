@extends('layout')

@section('content')

<h1 class="mb-4">Donor Pickup Management</h1>

<div class="card">

<div class="card-body">

<table class="table">

<thead>
<tr>
<th>ID</th>
<th>Address</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>

<tbody id="pickupTable"></tbody>

</table>

</div>

</div>

<script>

let pickups =
JSON.parse(localStorage.getItem("pickups")) || [];

function render(){

let html='';

pickups.forEach((p,index)=>{

html += `

<tr>

<td>${p.id}</td>
<td>${p.address}</td>
<td>${p.status}</td>

<td>

<button class="btn btn-success btn-sm"
onclick="updateStatus(${index},'Confirmed')">

Confirm

</button>

<button class="btn btn-primary btn-sm"
onclick="updateStatus(${index},'Completed')">

Complete

</button>

<button class="btn btn-danger btn-sm"
onclick="updateStatus(${index},'Cancelled')">

Cancel

</button>

</td>

</tr>

`;

});

pickupTable.innerHTML = html;

}

function updateStatus(index,status){

pickups[index].status=status;

localStorage.setItem(
"pickups",
JSON.stringify(pickups)
);

render();

}

render();

</script>

@endsection