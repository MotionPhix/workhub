<!DOCTYPE html>
<html>
<head>
  <title>{{ $filename }}</title>
  <style>
    table {
      width: 100%;
      border-collapse: collapse;
    }

    table, th, td {
      border: 1px solid #ddd;
      padding: 8px;
    }

    th {
      background-color: #f2f2f2;
    }
  </style>
</head>
<body>
<h1>{{ $filename }}</h1>
<table>
  <thead>
  <tr>
    @foreach(array_keys($data[0] ?? []) as $header)
      <th>{{ ucfirst(str_replace('_', ' ', $header)) }}</th>
    @endforeach
  </tr>
  </thead>
  <tbody>
  @foreach($data as $row)
    <tr>
      @foreach($row as $value)
        <td>{{ $value }}</td>
      @endforeach
    </tr>
  @endforeach
  </tbody>
</table>
</body>
</html>
