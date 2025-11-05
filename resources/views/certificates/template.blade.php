@php
    $studentName = isset($user) && property_exists($user, 'name') ? $user->name : 'John Doe';
    $courseTitle = isset($course) && property_exists($course, 'title') ? $course->title : 'Sample Course';
    $issueDate = isset($issueDate) ? $issueDate->format('d/m/Y') : now()->format('d/m/Y');
    $renewalDate = isset($renewalDate) ? $renewalDate->format('d/m/Y') : now()->addYear()->format('d/m/Y');
    $certificateCode = isset($certificateCode) ? $certificateCode : 'T-4C0J/77-S283J34';

    // Convert image to base64
    $imagePath = public_path('images/certificate_bg.png');
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/png;base64,' . $imageData;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certificate of Completion</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            width: 297mm;
            height: 210mm;
        }

        .certificate-wrapper {
            width: 297mm;
            height: 210mm;
            position: relative;
            overflow: hidden;
        }

        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            padding: 50px;
        }

        .main-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 80%;
        }

        .certificate-title {
            font-size: 48px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 30px;
            letter-spacing: 2px;
        }

        .awarded-to {
            font-size: 18px;
            color: #374151;
            margin-bottom: 15px;
        }

        .student-name {
            font-size: 56px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 20px 0;
            font-family: 'Brush Script MT', cursive;
            text-decoration: underline;
            text-decoration-color: #1e3a8a;
            text-underline-offset: 10px;
        }

        .completion-text {
            font-size: 18px;
            color: #374151;
            margin: 25px 0;
            line-height: 1.6;
        }

        .course-name {
            font-size: 32px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 20px 0;
        }

        .footer-section {
            position: absolute;
            bottom: 40px;
            left: 60px;
            font-size: 12px;
            color: #374151;
            line-height: 1.8;
        }

        .footer-section div {
            margin-bottom: 3px;
        }

        .signature-section {
            position: absolute;
            bottom: 80px;
            right: 80px;
            text-align: center;
        }

        .signature-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            border-top: 2px solid #1e3a8a;
            padding-top: 5px;
            margin-top: 10px;
        }

        .ornament {
            color: #1e3a8a;
            font-size: 24px;
            margin: 15px 0;
        }
    </style>
</head>

<body>
    <div class="certificate-wrapper">
        <!-- Background Image -->
        <img src="{{ $imageSrc }}" alt="" class="background-image">

        <!-- Content Layer -->
        <div class="content">
            <div class="main-content">
                <div class="certificate-title">CERTIFICATE OF COMPLETION</div>

                <div class="ornament">❖</div>

                <div class="awarded-to">This is to certify that</div>

                <div class="student-name">{{ $studentName }}</div>

                <div class="completion-text">
                    has successfully completed the course
                </div>

                <div class="course-name">{{ $courseTitle }}</div>

                <div class="ornament">❖</div>
            </div>

            {{-- <div class="footer-section">
                <div><strong>Issued On:</strong> {{ $issueDate }}</div>
                <div><strong>Recommended Renewal Date:</strong> {{ $renewalDate }}</div>
                <div><strong>Certificate Number:</strong> {{ $certificateCode }}</div>
                <div><strong>to visit:</strong> www.bbeducators.com</div>
            </div>

            <div class="signature-section">
                <div class="signature-title">Head of Academics & Research</div>
            </div> --}}
        </div>
    </div>
</body>

</html>
