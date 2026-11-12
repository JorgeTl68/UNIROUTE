<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\TransitRoute;
use Illuminate\Database\Seeder;

/**
 * Rutas de transporte de la UTC (TECSA Transportes), tomadas de los croquis de
 * Atención a Clientes: paradas y horario del 1er turno.
 *
 * Las paradas se ubicaron con OpenStreetMap (cruces de calles). "Cargo Gas
 * LEA" y "María Mazzarello" no aparecen en el mapa, así que se pusieron donde
 * las marca el croquis. El trazado se calculó una vez con OSRM siguiendo las
 * calles del croquis y se guarda ya codificado, igual que lo guarda el alta de
 * rutas, para no depender del servicio al sembrar.
 */
class TransitNetworkSeeder extends Seeder
{
    /**
     * @var array<string, array{latitude: float, longitude: float, has_shelter: bool}>
     */
    private const array STOPS = [
        'OXXO Santa Elena' => ['latitude' => 25.3520728, 'longitude' => -101.0220719, 'has_shelter' => false],
        'Carr. Zacatecas y Arizpe de la Maza' => ['latitude' => 25.3754773, 'longitude' => -101.011854, 'has_shelter' => false],
        'Prol. Urdiñola y Nazario Ortiz' => ['latitude' => 25.4272061, 'longitude' => -100.9833133, 'has_shelter' => false],
        'Luis Echeverría y Nazario Ortiz' => ['latitude' => 25.4386, 'longitude' => -100.97318, 'has_shelter' => false],
        'UTC' => ['latitude' => 25.5576848, 'longitude' => -100.9356719, 'has_shelter' => true],
        'Central de Autobuses' => ['latitude' => 25.3964881, 'longitude' => -101.0076395, 'has_shelter' => false],
        'Periférico LEA y Berriozábal' => ['latitude' => 25.4088715, 'longitude' => -101.0284361, 'has_shelter' => false],
        'Calz. Madero y Emilio Carranza' => ['latitude' => 25.4261241, 'longitude' => -101.0102751, 'has_shelter' => false],
        'Blvd. Coss y V. Carranza' => ['latitude' => 25.4328363, 'longitude' => -100.996023, 'has_shelter' => false],
        'Blvd. Revolución y Blvd. Mirasierra' => ['latitude' => 25.4175981, 'longitude' => -100.9204539, 'has_shelter' => false],
        'Blvd. Mirasierra y Eulalio Gutiérrez' => ['latitude' => 25.42973, 'longitude' => -100.95573, 'has_shelter' => false],
        'María Mazzarello y Calz. Salto del Agua' => ['latitude' => 25.50206, 'longitude' => -100.9361, 'has_shelter' => false],
        'Arco de las Lomas y Díaz Ordaz' => ['latitude' => 25.5196554, 'longitude' => -100.9379564, 'has_shelter' => false],
        'Blvd. del Valle y Carr. Saltillo–Monterrey' => ['latitude' => 25.53767, 'longitude' => -100.94242, 'has_shelter' => false],
        'Alameda de Arteaga' => ['latitude' => 25.44955, 'longitude' => -100.8605, 'has_shelter' => false],
        'OXXO Gas 4 de Octubre' => ['latitude' => 25.4456664, 'longitude' => -100.8923583, 'has_shelter' => false],
        'Blvd. Valdez Sánchez y Blvd. Minería' => ['latitude' => 25.442496, 'longitude' => -100.951195, 'has_shelter' => false],
        'Blvd. Valdez Sánchez y Periférico LEA' => ['latitude' => 25.4343598, 'longitude' => -100.9720303, 'has_shelter' => false],
        'Manuel W. González y Periférico LEA' => ['latitude' => 25.3978556, 'longitude' => -101.0099935, 'has_shelter' => false],
        'OXXO Valle Dorado' => ['latitude' => 25.3893629, 'longitude' => -100.9952854, 'has_shelter' => false],
        'Cargo Gas LEA' => ['latitude' => 25.4147764, 'longitude' => -100.975949, 'has_shelter' => false],
        'Blvd. Nazario Ortiz y Carr. Saltillo–Monterrey' => ['latitude' => 25.4697858, 'longitude' => -100.9783519, 'has_shelter' => false],
    ];

    /**
     * Cada parada con la hora del croquis ("HH:MM"): la primera es la salida y
     * la última, la llegada a la UTC.
     *
     * @var list<array{number: int, name: string, description: string, color: string, stops: list<array{0: string, 1: string}>}>
     */
    private const array ROUTES = [
        [
            'number' => 1,
            'name' => 'Santa Elena',
            'description' => 'Desde Santa Elena por la carretera a Zacatecas, Urdiñola y Blvd. Nazario Ortiz.',
            'color' => '#91db2a',
            'stops' => [
                ['OXXO Santa Elena', '06:40'],
                ['Carr. Zacatecas y Arizpe de la Maza', '06:50'],
                ['Prol. Urdiñola y Nazario Ortiz', '07:05'],
                ['Luis Echeverría y Nazario Ortiz', '07:20'],
                ['UTC', '07:45'],
            ],
        ],
        [
            'number' => 2,
            'name' => 'Central',
            'description' => 'Sale de la Central de Autobuses por el Periférico LEA, Calz. Madero y Blvd. Coss.',
            'color' => '#e0c297',
            'stops' => [
                ['Central de Autobuses', '06:35'],
                ['Periférico LEA y Berriozábal', '06:45'],
                ['Calz. Madero y Emilio Carranza', '07:10'],
                ['Blvd. Coss y V. Carranza', '07:25'],
                ['UTC', '07:45'],
            ],
        ],
        [
            'number' => 7,
            'name' => 'Mirasierra',
            'description' => 'Por Blvd. Mirasierra, Eulalio Gutiérrez y Salto del Agua hasta Ramos Arizpe.',
            'color' => '#7dd3fc',
            'stops' => [
                ['Blvd. Revolución y Blvd. Mirasierra', '06:35'],
                ['Blvd. Mirasierra y Eulalio Gutiérrez', '06:50'],
                ['María Mazzarello y Calz. Salto del Agua', '07:00'],
                ['Arco de las Lomas y Díaz Ordaz', '07:10'],
                ['Blvd. del Valle y Carr. Saltillo–Monterrey', '07:20'],
                ['UTC', '07:45'],
            ],
        ],
        [
            'number' => 11,
            'name' => '4 de Octubre',
            'description' => 'Desde Arteaga por Blvd. Valdez Sánchez y el Periférico LEA.',
            'color' => '#c4b5fd',
            'stops' => [
                ['Alameda de Arteaga', '06:45'],
                ['OXXO Gas 4 de Octubre', '06:55'],
                ['Blvd. Valdez Sánchez y Blvd. Minería', '07:05'],
                ['Blvd. Valdez Sánchez y Periférico LEA', '07:10'],
                ['UTC', '07:45'],
            ],
        ],
        [
            'number' => 12,
            'name' => 'Luis Echeverría',
            'description' => 'Por el Periférico LEA, Blvd. Nazario Ortiz y la carretera a Monterrey.',
            'color' => '#fda4af',
            'stops' => [
                ['Manuel W. González y Periférico LEA', '06:40'],
                ['OXXO Valle Dorado', '06:50'],
                ['Cargo Gas LEA', '07:05'],
                ['Blvd. Nazario Ortiz y Carr. Saltillo–Monterrey', '07:20'],
                ['UTC', '07:45'],
            ],
        ],
    ];

    /**
     * Trazado por las calles (polilínea de OSRM, precisión 5) por número de ruta.
     *
     * @var array<int, string>
     */
    private const array POLYLINES = [
        1 => 'irvyC~zqgR?B@Z@b@FXkCcA[MoCcAME}@[qFiBaDiAyFsBiAa@}@_@iE}AaKkDmBq@eE_BGCqGyBMGwGcCaBm@gA_@sDiAeBq@w@YgBs@[KSGoBw@c@OoDoAICYIi@UyAg@uBu@WI_J{C}@[uDsA}Bw@[Kw@YyAi@y@[g@Qi@Oo@SmBq@KEg@QMEoAc@]KKEeBk@oG{BwAi@WIw@YsDoAoE_BqAg@iAa@o@SyEeBUKiAa@w@Y{@WsAg@y@[cA_@s@WgA_@yAk@aA_@YKe@SeAa@gCaAm@UKEQGEAMEkCaAWIeA]cA[k@Ss@UgA]u@UeA_@UMq@Wc@MQGQGeAe@aDkA_A]c@MyAg@SGSIg@Sk@Su@WaA[}@YyAm@}@[}@_@sAg@gBm@mDoASIQGkC}@sDqAaBm@sAe@mDmAMGEAWKMEQGcC{@}@]QGs@YUIYKaCy@_Bm@aBm@}Ak@y@W]MwAc@yAe@wCcAOEM@kBs@m@S_A]yAi@aBk@oBs@_Cy@wFsB_A[gBo@cC}@{Bw@cA_@gFiBcC{@cDkA_DiAsBs@_@MaBm@gDkAsBu@y@YeAa@iAa@gC}@{DwAk@S}@Y[M{@[}DuAuDuAlAoElAoEvAkFh@yBp@?fBBL@@WkCsAMGeDcBw@]eC_AUIeDoAgBm@[Ii@My@UmDeAkCs@uDeAqBo@MOCAo@[SOQUQc@uAuEKYOScAiAqDgEIMIWWqBWaC]}BIWKW[i@IKi@s@GIY]g@g@m@q@k@k@q@c@cA_@{A]uBa@oE{@}AW{Ey@iDo@u@Ki@KmCe@w@Ki@GMAeAEgAAk@?i@BWBYDw@JmATsA^mAb@e@Rc@Rg@ZoGbEKFcAt@eEvC{E`D}AdAsFxD{CvBoAz@{E|Co@\\e@Pq@P{Ch@k@JeBZcDn@uAXuHvASD{@PWDE@_@HOB]HYAQ?UBkDr@kAT]FgAPo@Ji@D]?GAm@A[Cg@Ks@Qm@Q]KmBc@aDiA{Ae@e@Mi@Qe@OeA]cV{IwCkA_Bk@eBq@qToIsEcByEiBc@QyAi@aA_@kAe@OGyEgBKG{CeAq@Y}FyBq@WiAc@w@[IE}DwAUK_FkBeA]{@a@aBq@OG{Ai@QGw@YOGe@QOIy@[m@WaBq@eEgBaE}AGCQGcAe@u@Y{CqAu@[[MQGCCkAe@ECm@YGEo@]_@UMI_BaAcAo@aCoAuAq@iDiByC}AQIMIMGa@UKGg@Yi@YkAq@aCqAmB{@[ICAa@Ii@Gs@CgEFeJ\\K@qDFkBBeAAeAIkASq@U_Bm@mDcBsBcAoK}Ea@QiKwEs@]}VsLSK{K{Eq@Ya@Oe@Qw@S{Cw@gCo@e@KuDy@MC}A]e@KkBa@{Cw@aDu@[GQE_AW_@I}EeA}D_Ay@Sk@Qg@QqAo@aAk@iDqCi@g@yBkBaCqBiAaAoGqFeEqD}JaJwCsCUSuEcEmGuFs@q@iFwEu@q@uCcCuDgDcFcF}@gAU[{FeI]c@cGqIeEyF_AsAs@kAyRkXo@{@_EuFEMEKQWqBqCa@k@SUQS[Yi@i@_@_@a@_@m@e@QGOGMIg@a@UMGCG?I?KDGDGHEDCN?J?HBJBFHPv@d@f@Zp@^XRVRVRRPTVTXb@l@RX|@pA|D`GjA~A`@j@hA|A~BdDjCrD|AdCHt@BXA^Gl@K\\KL}@v@cAdACBmAtAGH]`@a@t@[t@Wv@]nACFELKTwBjDa@j@MPY`@ORON]NWHo@TKDOHIFIHKNe@r@',
        2 => 'ag_zCv`ogRsBnEq@vAIPc@|@q@lAKNaApBEHo@tAm@rAQ^c@|@OZILMVWd@e@bAe@`AKTWh@gA|BQ\\i@fAk@hAKT}BvEQ`@[j@IPa@v@_@z@Gf@Uj@c@lAc@lAc@tAI^G^@XDTDNHNRPVRtBz@x@`@`@^d@h@\\d@R`@\\x@Rt@p@nB^dALp@Dh@@z@B`CBtC?`A?j@?H?PI\\GPQ`@CB_@b@WXYXsAdBSHa@d@OP[ZONy@z@aBxBWXUVS^Kd@A^@\\FTDNFLNT^^l@n@R\\N^ZlAJf@Jb@Bd@?`@Eb@GXO^]^]Ro@Ra@@a@Ga@QqAq@_Ac@]I[Cq@@kBLM@QAYCOCMGGCEEcA{@wBoB{BqBkAaAqAoAg@]m@[o@a@m@a@i@]iAu@qAcAkDuCgDsCmDuCkDsCiDqCkDsC_DsCSQu@s@y@s@{@w@}@w@uAcAwDiCYMa@Ii@KgGw@mC[yEo@q@CYIu@M}@IoAOKAUCc@E{AOw@Oy@Sa@MUMIGkCoB_BoA_@]k@e@}@u@w@m@_@Se@IS@OFm@b@k@j@WL]JaBy@}@_@k@YRg@zAmD\\cAZw@Pe@Ri@Zy@Zs@fD{Ib@iAfBwENa@t@wBaGcDQI}DaCuEgC]Qo@]{BoA_B{@ECuAy@e@Uq@_@YO{DwBw@YUIcDsA[KKEQ_@q@u@QSCGKSOa@Eg@?i@Z_B^{Bd@aCn@wE^aC^yBZoBj@uDv@{En@wBn@cCOG]MOEmEuAeAW_@KmAc@wDwAI?uD}AEA{D{A}DwAsCcAICaFkBQGsDwAoCaA[MqEaB}DuASIoDqA_FiBg@Qm@UUIQIm@UmDsAiE}AOGmAc@GCy@]_@MuGmC[OsAc@mCgAKEg@Qs@WoEeBk@U]OOG{@]{B}@_DmAMGCA{B{@ME}@]ICs@[kAe@m@UOGuB}@WIgAc@{@[}CmA_@Ow@Yu@[{Am@GCIC_DiAgAa@iAa@ICQIsBw@gAg@yAaAm@_@YQWKkAa@cCs@aC}@aDiA{Ae@e@Mi@Qe@OeA]cV{IwCkA_Bk@eBq@qToIsEcByEiBc@QyAi@aA_@kAe@OGyEgBKG{CeAq@Y}FyBq@WiAc@w@[IE}DwAUK_FkBeA]{@a@aBq@OG{Ai@QGw@YOGe@QOIy@[m@WaBq@eEgBaE}AGCQGcAe@u@Y{CqAu@[[MQGCCkAe@ECm@YGEo@]_@UMI_BaAcAo@aCoAuAq@iDiByC}AQIMIMGa@UKGg@Yi@YkAq@aCqAmB{@[ICAa@Ii@Gs@CgEFeJ\\K@qDFkBBeAAeAIkASq@U_Bm@mDcBsBcAoK}Ea@QiKwEs@]}VsLSK{K{Eq@Ya@Oe@Qw@S{Cw@gCo@e@KuDy@MC}A]e@KkBa@{Cw@aDu@[GQE_AW_@I}EeA}D_Ay@Sk@Qg@QqAo@aAk@iDqCi@g@yBkBaCqBiAaAoGqFeEqD}JaJwCsCUSuEcEmGuFs@q@iFwEu@q@uCcCuDgDcFcF}@gAU[{FeI]c@cGqIeEyF_AsAs@kAyRkXo@{@_EuFEMEKQWqBqCa@k@SUQS[Yi@i@_@_@a@_@m@e@QGOGMIg@a@UMGCG?I?KDGDGHEDCN?J?HBJBFHPv@d@f@Zp@^XRVRVRRPTVTXb@l@RX|@pA|D`GjA~A`@j@hA|A~BdDjCrD|AdCHt@BXA^Gl@K\\KL}@v@cAdACBmAtAGH]`@a@t@[t@Wv@]nACFELKTwBjDa@j@MPY`@ORON]NWHo@TKDOHIFIHKNe@r@',
        7 => '_kczCz_~fRB@D@F?FADCDEDEBG?G@GAGCGEIGEICG?G?GBIFEJALeATaAPcCh@cCh@]HcAT_B`@{Bf@uAZKBKB}@RyBf@uAZaCf@cAT{AZoAXaIhBkDv@kCl@QDyGvAqCn@qEfAiE~@sCn@eC\\DXPvBZvCtAlF^xBv@|Jl@|HrB~XNzBz@hLNbCb@jFt@lJbAzNb@lG`@~FN~ADl@ZbE\\vEPlCAbCAnD@vCRnCXlD@V@JMFKDE@eBd@iAZsAZwDbAgAZQFQJMJ[\\QTMVKRMZ[x@MXMVKJKHIDKDM@_DXaDV_E\\{D\\{@FOBQBw@Lu@L{AXiBX}B`@oDj@g@FOBO@K?I?y@ISCSEQGUEc@Km@M[Ic@K{@UgAW_@KaDw@aCi@mA[UGcEcAaGwAcBa@sA[SE}EmAOC_@KCA}Cu@UImCm@aHcBw@Q[IcAWo@OkGyAaAY}D_AQEqA[eCk@YG_AU_Du@aAWQEm@OwBi@iHkBgCo@CAICuBg@}@SQEUGw@Sa@KoA[q@Qq@Oe@MiFmAWGSEMEc@K]IuAYYGa@I]IYKYM}@g@cBcAg@[{A_Ac@W{AaAqAu@SMmBoAs@}@i@s@wAgB]c@}AmBu@_AMMMMOKMIOK[MYKWG]Ik@MoCi@]IYIOGSI]Qe@]g@Wo@[qAw@a@Uc@Wa@UMMUOo@g@SQe@a@u@o@iAaAeA}@WSYYMOs@w@MMYSaBqA]WOKkBgA_Ak@q@c@YQeAm@qAw@w@a@sBqAgAq@s@_@[QkAg@aBs@y@[m@Sg@Ug@Sg@Yi@Ua@Qi@Se@Mm@Qy@Oo@Ky@Qo@Ka@Ic@EmAKSEy@Mq@OgB_@kBa@iMmCWG}@OOCuBc@c@GUAs@AqHBiDBWBgBToARs@JsAR_@F[Dc@FmAPk@Hg@Fo@DcAD{@Bo@B{@DgAFy@Be@DS@{@L}AVeANiATcARk@NwAd@iBl@}Ad@cCx@_Bd@gA`@ODQF{@X_Bb@c@NcA\\aAX_@Hi@FgALu@HyAPu@HSBO?k@?kAISCqBSe@Gk@IuAUu@ImAKeBIeBMkAGUCKCGEGCGE_@Si@YWKMEOEQESCWCeAIw@Iq@Kc@C[CuBMuCW{AzAS@I@G@g@PYHkA^i@PgFxAc@Lw@VgElA[F_AXiBd@u@T}FbB[Qq@_@cCkA{CyAgAg@qAo@sAo@oAm@mAm@c@UeAg@qAm@aAe@cAe@_Ae@eAi@g@S[Is@We@Um@_@c@YUMc@f@gArAGFq@`AYb@]h@CLANKdE?ZAPAJENY|@_@rASr@Qn@e@|Aa@|A_@|AMd@M^Yv@Sf@]|@Yp@_BYwAUOCkAQ{AYa@GcBUOCSE_Ca@q@KsDq@MCm@IE?MD[GA??EACAAEEGCYEg@E_AGw@ESCUEMGMIc@_@c@[]KYGs@q@iFwEu@q@uCcCuDgDcFcF}@gAU[{FeI]c@cGqIeEyF_AsAs@kAyRkXo@{@_EuFEMEKQWqBqCa@k@SUQS[Yi@i@_@_@a@_@m@e@QGOGMIg@a@UMGCG?I?KDGDGHEDCN?J?HBJBFHPv@d@f@Zp@^XRVRVRRPTVTXb@l@RX|@pA|D`GjA~A`@j@hA|A~BdDjCrD|AdCHt@BXA^Gl@K\\KL}@v@cAdACBmAtAGH]`@a@t@[t@Wv@]nACFELKTwBjDa@j@MPY`@ORON]NWHo@TKDOHIFIHKNe@r@',
        11 => 'mrizC`irfRLhCBtADxCBlCDtCDnC?^?`@@nADnCFpCLnCJnAj@bHFr@Fv@Dd@Bf@Hx@JzANfBJrANbBJvANbBNxAB`@Z~DHlA@x@A|A?J?HEt@Ex@A^Ix@Kt@W|AIb@Mh@Qf@s@nBaCvFgAdCiC|F[v@Sl@Mh@Gn@Cr@@h@Fd@FZJ^L^NXFJHHtCxCVVxArAj@l@NP^^LL\\^TVXb@NVP\\LXPb@JTd@vAxCfIl@`Bx@|BV|@HTZx@FPTr@f@|A^rAXlARv@H`@@FH`@Jh@@DFb@J`AJ~@Dn@NfBTvCVtDJxA@JHjARbDPvCLbBNhALnBHx@\\rEnAjP@Xn@lIDd@N`BPfCd@vG@J?Hf@pGd@vFFn@f@lG@VJdAFx@z@zKVbDDf@Fz@Db@NtBb@vFFn@@RZ`EZ|DV|CPxB@PHbALfBBT?L@T?P?P?ZA`@Gn@Kh@Qf@}@xBKTm@jAYh@mCfFSh@Sz@Q`ACj@C~@@pC?vBAb@Cj@Il@Ib@Qn@g@xASj@kEtMSn@Sr@U|@On@ADAF?F@JIf@Gf@Aj@Ad@@d@Bf@Df@BZTrC@L\\|EX|CZdD@^AP?L@Hv@xID\\p@fI@Jb@hFL`BD^Fp@Hz@VfDZlD`@zEBTb@nFn@tHJhAJvA\\|DBXj@zGpApQf@hGFr@Bb@DXJr@Hf@Ld@JXJXLVR^RZjFhHj@x@hD|EfCpD|JhNlAnBRZf@nATt@Jt@Z~BBLJt@^KGm@AEAQKy@E[TERCJCD?D@BBNb@Pd@E@c@NUJSFa@HKBe@JSFwAXo@NqBj@yCt@EBe@PuBz@aALU?e@Eu@[m@q@k@k@q@c@cA_@{A]uBa@oE{@}AW{Ey@iDo@u@Ki@KmCe@w@Ki@GMAeAEgAAk@?i@BWBYDw@JmATsA^mAb@e@Rc@Rg@ZoGbEKFcAt@eEvC{E`D}AdAsFxD{CvBoAz@{E|Co@\\e@Pq@P{Ch@k@JeBZcDn@uAXuHvASD{@PWDE@_@HOB]HYAQ?UBkDr@kAT]FgAPo@Ji@D]?GAm@A[Cg@Ks@Qm@Q]KmBc@aDiA{Ae@e@Mi@Qe@OeA]cV{IwCkA_Bk@eBq@qToIsEcByEiBc@QyAi@aA_@kAe@OGyEgBKG{CeAq@Y}FyBq@WiAc@w@[IE}DwAUK_FkBeA]{@a@aBq@OG{Ai@QGw@YOGe@QOIy@[m@WaBq@eEgBaE}AGCQGcAe@u@Y{CqAu@[[MQGCCkAe@ECm@YGEo@]_@UMI_BaAcAo@aCoAuAq@iDiByC}AQIMIMGa@UKGg@Yi@YkAq@aCqAmB{@[ICAa@Ii@Gs@CgEFeJ\\K@qDFkBBeAAeAIkASq@U_Bm@mDcBsBcAoK}Ea@QiKwEs@]}VsLSK{K{Eq@Ya@Oe@Qw@S{Cw@gCo@e@KuDy@MC}A]e@KkBa@{Cw@aDu@[GQE_AW_@I}EeA}D_Ay@Sk@Qg@QqAo@aAk@iDqCi@g@yBkBaCqBiAaAoGqFeEqD}JaJwCsCUSuEcEmGuFs@q@iFwEu@q@uCcCuDgDcFcF}@gAU[{FeI]c@cGqIeEyF_AsAs@kAyRkXo@{@_EuFEMEKQWqBqCa@k@SUQS[Yi@i@_@_@a@_@m@e@QGOGMIg@a@UMGCG?I?KDGDGHEDCN?J?HBJBFHPv@d@f@Zp@^XRVRVRRPTVTXb@l@RX|@pA|D`GjA~A`@j@hA|A~BdDjCrD|AdCHt@BXA^Gl@K\\KL}@v@cAdACBmAtAGH]`@a@t@[t@Wv@]nACFELKTwBjDa@j@MPY`@ORON]NWHo@TKDOHIFIHKNe@r@',
        12 => 'so_zCloogRVPJSvCeGnB_EHQTi@nByDjA{BZs@x@aB|@oBhAyBj@gANWd@kANY|@eB|A_DZk@rBmE|BoEpD}Hh@kAN]Pa@r@}Az@mBTg@Vm@LYLYr@cBdB}DfAcCRk@N_@Dk@Fg@@_@Bi@Ag@Ck@Ea@EWIa@Ka@I[G[KYCECGGEGEMEQEg@KSIoAI_BM{DYeCQmCSeBMaDWaHk@[Im@EA?{@GY@cAGq@G}Gi@kCSuDYa@GYEWKWK]QSQWUOQUWS]m@_AS[}BoDw@iAa@m@aA}AgAaB{BuDsBiDs@oACEeFsIqD{F{@iAe@a@i@_@m@Yy@U}@QgBWaF}@iDg@}AUgBYyB_@gAOOAWEqAO_Bk@UISISKs@[[OUMOKKKKKS[U[k@_Aq@w@m@k@_As@OIKGw@i@_@WIAEAGA_@WuAkAwAsAKIEAE?K?aAaAaD{CwBqBsAqAcAcAcCcCg@i@e@a@i@c@kAu@m@Wu@Wo@OsAW}A[}A[QEgAOy@G_ACg@@i@DkDX_CVqAPuAVqDr@A?gEbAk@N_HzAy@R[FgAVw@RkHdBgBb@g@DiBRkAR{Bf@wBl@ODWFMDYHG@EB_@TEBe@PuBz@aALU?e@Eu@[m@q@k@k@q@c@cA_@{A]uBa@oE{@}AW{Ey@iDo@u@Ki@KmCe@w@Ki@GMAeAEgAAk@?i@BWBYDw@JmATsA^mAb@e@Rc@Rg@ZoGbEKFcAt@eEvC{E`D}AdAsFxD{CvBoAz@{E|Co@\\e@Pq@P{Ch@k@JeBZcDn@uAXuHvASD{@PWDE@_@HOB]HYAQ?UBkDr@kAT]FgAPo@Ji@D]?GAm@A[Cg@Ks@Qm@Q]KmBc@aDiA{Ae@e@Mi@Qe@OeA]cV{IwCkA_Bk@eBq@qToIsEcByEiBc@QyAi@aA_@kAe@OGyEgBKG{CeAq@Y}FyBq@WiAc@w@[IE}DwAUK_FkBeA]{@a@aBq@OG{Ai@QGw@YOGe@QOIy@[m@WaBq@eEgBaE}AGCQGcAe@u@Y{CqAu@[[MQGCCkAe@ECm@YGEo@]_@UMI_BaAcAo@aCoAuAq@iDiByC}AQIMIMGa@UKGg@Yi@YkAq@aCqAmB{@[ICAa@Ii@Gs@CgEFeJ\\K@qDFkBBeAAeAIkASq@U_Bm@mDcBsBcAoK}Ea@QiKwEs@]}VsLSK{K{Eq@Ya@Oe@Qw@S{Cw@gCo@e@KuDy@MC}A]e@KkBa@{Cw@aDu@[GQE_AW_@I}EeA}D_Ay@Sk@Qg@QqAo@aAk@iDqCi@g@yBkBaCqBiAaAoGqFeEqD}JaJwCsCUSuEcEmGuFs@q@iFwEu@q@uCcCuDgDcFcF}@gAU[{FeI]c@cGqIeEyF_AsAs@kAyRkXo@{@_EuFEMEKQWqBqCa@k@SUQS[Yi@i@_@_@a@_@m@e@QGOGMIg@a@UMGCG?I?KDGDGHEDCN?J?HBJBFHPv@d@f@Zp@^XRVRVRRPTVTXb@l@RX|@pA|D`GjA~A`@j@hA|A~BdDjCrD|AdCHt@BXA^Gl@K\\KL}@v@cAdACBmAtAGH]`@a@t@[t@Wv@]nACFELKTwBjDa@j@MPY`@ORON]NWHo@TKDOHIFIHKNe@r@',
    ];

    /**
     * Días con servicio: lunes (1) a viernes (5).
     *
     * @var list<int>
     */
    private const array SERVICE_WEEKDAYS = [1, 2, 3, 4, 5];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campusId = Campus::where('slug', CampusSeeder::UTC)->value('id');
        $stopIds = collect(self::STOPS)
            ->map(fn (array $attributes, string $name): int => Stop::updateOrCreate(['name' => $name], $attributes)->id);

        foreach (self::ROUTES as $data) {
            // Las horas "HH:MM" se ordenan igual como texto.
            $times = array_column($data['stops'], 1);
            $departure = min($times);
            $arrival = max($times);

            // Sale una vez por turno, así que no tiene frecuencia fija.
            $route = TransitRoute::updateOrCreate(
                ['campus_id' => $campusId, 'number' => $data['number']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'color' => $data['color'],
                    'encoded_polyline' => self::POLYLINES[$data['number']],
                    'frequency_minutes' => null,
                    'service_starts_at' => $departure.':00',
                    'service_ends_at' => $arrival.':00',
                    'is_active' => true,
                ],
            );

            $pivot = [];
            foreach ($data['stops'] as $position => [$stopName, $time]) {
                $pivot[$stopIds[$stopName]] = [
                    'position' => $position + 1,
                    'minutes_from_start' => $this->minutes($time) - $this->minutes($departure),
                ];
            }
            $route->stops()->sync($pivot);

            $this->seedSchedules($route, $departure);
        }
    }

    /**
     * Una salida por día de servicio a la hora de la primera parada.
     */
    private function seedSchedules(TransitRoute $route, string $departure): void
    {
        $route->schedules()->delete();
        $now = now();

        Schedule::insert(array_map(fn (int $weekday): array => [
            'route_id' => $route->id,
            'weekday' => $weekday,
            'departs_at' => $departure.':00',
            'created_at' => $now,
            'updated_at' => $now,
        ], self::SERVICE_WEEKDAYS));
    }

    /**
     * Minutos desde la medianoche de una hora "HH:MM".
     */
    private function minutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return (int) $hours * 60 + (int) $minutes;
    }
}
