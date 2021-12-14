<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RESULTADOS DE ANÁLISIS EMPRESARIAL</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&family=Poppins:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap"
        rel="stylesheet">
</head>

<body style="margin:0;">
    <style>
        body {
            font-family: 'Lato', sans-serif;
            font-family: 'Poppins', sans-serif;
        }

        .container {
            text-align: center;
            background: rgb(92, 18, 201);
            background: linear-gradient(90deg, rgba(92, 18, 201, 1) 0%, rgba(6, 143, 251, 1) 100%);
            position: relative;
        }

        .wave-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
        }

        .kap-logo {
            width: 200px;
            background-color: white;
            border-radius: 1rem;
            padding: 0.5rem;
        }

        .nav-container {
            text-align: left;
            padding: 1rem;
        }

        .cclam-logo {
            width: 105px;
        }

        .result-resume {
            display: flex;
            width: 100%;
            padding-bottom: 3rem;
        }

        .result-bars-graph {
            width: 500px;
            background-color: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin:auto;
        }

        .result-p {
            color: #576071;
            display: block;
            margin: 0;
            font-size: 1rem;
            text-align: center;
        }

        .result-probabilidad {
            display: block;
            font-size: 3rem;
            margin-bottom: 2rem;
        }

        .criteria-result {
            color: rgba(56, 98, 124, 0.71);
            font-weight: bold;
            display: block;
            text-align: start;
        }

        .criteria-bar-container {
            margin-top: 0.5rem;
            background-color: rgb(218, 218, 218);
            width: 100%;
            height: 1rem;
            border-radius: 2rem;
        }

        .criteria-probability {
            display: block;
            text-align: end;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .criteria-bar-fill {
            background-color: rgb(105, 217, 152);
            height: 1rem;
            border-radius: 2rem;
        }

        .high {
            color: rgb(105, 217, 152);
        }

        .medium {
            color: #fdb659;
        }

        .low {
            color: #ef6085;
        }

        .bg-high {
            background-color: rgb(105, 217, 152);
        }

        .bg-medium {
            background-color: #fdb659;
        }

        .bg-low {
            background-color: #ef6085;
        }

        .criteria-result-detail {
            color: #576071;
            font-weight: 600;
            display: block;
            text-align: start;
            font-size: 2.3rem;
        }

        .criteria-comments-section {
            padding: 3rem 9vw;
        }

        .comment-icon {
            width: 16px;
            color: #1974f0;
            margin-right: 0.5rem;
            fill: #1974f0;
        }

        .comment {
            font-size: 1rem;
        }

        .client-name {
            line-height: 2rem;
            margin-top: 0;
            color: #1978c3;
            text-align: center;
        }
    </style>
    <div class="container">
        <div class="nav-container">
            <img class="kap-logo" src="https://cclam.org.pe/recursos.base/public/storage/kap/kaplogo.png" />
            <img class="cclam-logo" src="https://cclam.org.pe/recursos.base/public/storage/logocclam.png" />
        </div>
        <div class="result-resume">
            <div class="result-bars-graph">
                <div>
                    <h1 class="client-name">{{ $demo['client'] }}</h1>
                    <p class="result-p">Su probabilidad de crecimiento general es:</p>
                    @if ($demo['general_probability'] == "Alta")
                        <strong class="result-probabilidad high">{{ $demo['general_probability'] }}</strong>
                    @else
                        @if ($demo['general_probability'] == "Media")
                            <strong class="result-probabilidad medium">{{ $demo['general_probability'] }}</strong>
                        @else
                            <strong class="result-probabilidad low">{{ $demo['general_probability'] }}</strong>
                        @endif
                    @endif
                    <img src="https://cclam.org.pe/recursos.base/public/storage/kap/high.png"
                        style="max-width: 300px;" />
                </div>
                @foreach ($demo['criteria'] as $criteria)
                    <div style="margin-top: 2rem;">
                        <span class="criteria-result">{{ $criteria['criteria'] }}</span>
                        <div class="criteria-bar-container">
                        @if ($criteria['criteria_probability_number'] == "2")
                            <div class="criteria-bar-fill bg-high" style="width: {{ $criteria['percent'] }}%;"></div>
                        @else
                            @if ($criteria['criteria_probability_number'] == "1")
                            <div class="criteria-bar-fill bg-medium" style="width: {{ $criteria['percent'] }}%;"></div>
                            @else
                            <div class="criteria-bar-fill bg-low" style="width: {{ $criteria['percent'] }}%;"></div>
                            @endif
                        @endif
                        </div>
                        @if ($criteria['criteria_probability_number'] == "2")
                            <span class="criteria-probability high">{{ $criteria['criteria_probability'] }}</span>
                        @else
                            @if ($criteria['criteria_probability_number'] == "1")
                                <span class="criteria-probability medium">{{ $criteria['criteria_probability'] }}</span>
                            @else
                                <span class="criteria-probability low">{{ $criteria['criteria_probability'] }}</span>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="criteria-comments-section">
        @foreach ($demo['comments'] as $comment)
            <div class="criteria-comments comments">
                <div style="display: flex; justify-content: space-between;"><span
                        class="criteria-result-detail">{{ $comment['criteria'] }}</span><span class="criteria-result-detail" style="margin-left: auto;">{{ $comment['points'] }}%</span></div>
                <div style="text-align: left; color: rgb(87, 96, 113);">
                <p style="font-size: 1rem;opacity: 80%;">Sugerencias de mejora:</p>
                    <ul>
                        @foreach ($comment['comments'] as $commentParaph)
                            <li>
                                <span class="comment">
                                    {{ $commentParaph }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
</body>

</html>