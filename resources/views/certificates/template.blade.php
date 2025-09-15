<!DOCTYPE html>
<html>
<head>
    <title>Certificate of Completion</title>
    <style>
        body { text-align: center; font-family: sans-serif; }
        h1 { font-size: 40px; }
        h2 { font-size: 25px; }
        p { font-size: 18px; }
    </style>
</head>
<body>
    <h1>Certificate of Completion</h1>
    <p>This is to certify that</p>
    <h2>{{ $user->name }}</h2>
    <p>has successfully completed the course</p>
    <h2>{{ $course->title }}</h2>
    <p>Issued on {{ $issued_at->format('d M Y') }}</p>
    <p>Certificate Code: {{ $code }}</p>
</body>
</html>
