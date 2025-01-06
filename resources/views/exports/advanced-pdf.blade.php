<!DOCTYPE html>
<html>
<head>
  <title>{{ $options['title'] }}</title>
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
      background-color: {{ $options['styling']['header_color'] }};
      color: #fff;
    }

    tr:nth-child(even) {
      background-color: {{ $options['styling']['alternate_row_color'] }};
    }
  </style>
</head>
<body>
<h1>{{ $options['title'] }}</h1>
<table>
  <thead>
  <tr>
    @foreach($options['columns'] as $header)
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
