@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Commission Dashboard</h3>

    <form method="GET" action="{{ route('dashboard') }}" class="d-flex">
        <label class="me-2 align-self-center">Month:</label>
        <select name="month" class="form-select me-2" onchange="this.form.submit()">
            @foreach($monthOptions as $option)
                <option value="{{ $option['value'] }}" {{ $option['value'] === $selectedMonth ? 'selected' : '' }}>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
        <noscript>
            <button class="btn btn-primary">Apply</button>
        </noscript>
    </form>
</div>

<div class="mb-3">
    <strong>Reward Pool (1% of system revenue):</strong>
    {{ number_format($pool, 0, ',', '.') }} VND
</div>

<table class="table table-striped table-bordered align-middle">
    <thead class="table-dark">
    <tr>
        <th>User</th>
        <th class="text-end">Personal Sales</th>
        <th class="text-end">Branch Sales</th>
        <th class="text-center">Qualified Branches</th>
        <th class="text-center">Is Eligible</th>
        <th class="text-end">Commission</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row['user']->name }}</td>
            <td class="text-end">{{ number_format($row['personalSales'], 0, ',', '.') }}</td>
            <td class="text-end">{{ number_format($row['branchSales'], 0, ',', '.') }}</td>
            <td class="text-center">{{ $row['qualifiedBranches'] }}</td>
            <td class="text-center">
                @if($row['isEligible'])
                    <span class="badge bg-success">YES</span>
                @else
                    <span class="badge bg-secondary">NO</span>
                @endif
            </td>
            <td class="text-end">
                {{ number_format($row['commission'], 0, ',', '.') }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection