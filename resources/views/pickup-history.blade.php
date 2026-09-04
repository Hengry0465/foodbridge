@extends('layout')

@section('content')

<h1 class="mb-4">Pickup History</h1>

<div class="card">

<div class="card-body">

<input class="form-control mb-3"
placeholder="Search..."
id="searchBox">

<table class="table">

<thead>
<tr>
<th>ID</th>
<th>Address</th>
<th>Date</th>
<th>Status</th>
</tr>
</thead>

<tbody id="historyTable"></tbody>

</table>

</div>

</div>

<script>

let pickups =
JSON.parse(localStorage.getItem("pickups")) || [];

function render(){

let search =
document.getElementById("searchBox")
.value.toLowerCase();

let html='';

pickups
.filter(p =>
(p.address||'')
.toLowerCase()
.includes(search))
.forEach(p=>{

html += `
<tr>
<td>${p.id}</td>
<td>${p.address}</td>
<td>${p.datetime}</td>
<td>${p.status}</td>
</tr>
`;

});

historyTable.innerHTML = html;

}

document
.getElementById("searchBox")
.addEventListener("keyup",render);

render();

</script>

@endsection
``