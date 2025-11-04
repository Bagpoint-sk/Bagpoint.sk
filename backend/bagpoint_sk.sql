--
-- PostgreSQL database dump
--

\restrict uQqhcue5ScAu1Y7t4SACydCfQKStuXGfyDrxtF0vZDodLg0pAVaopdd0WtyaMV1

-- Dumped from database version 17.6 (Debian 17.6-2.pgdg12+1)
-- Dumped by pg_dump version 17.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: bagpoint_sk_user
--

-- *not* creating schema, since initdb creates it


ALTER SCHEMA public OWNER TO bagpoint_sk_user;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: boxes; Type: TABLE; Schema: public; Owner: bagpoint_sk_user
--

CREATE TABLE public.boxes (
    box_id integer NOT NULL,
    box_name character varying(10) NOT NULL,
    size character varying(1) NOT NULL,
    location character varying(50) NOT NULL,
    price_per_day numeric(10,2) NOT NULL,
    status character varying(10) NOT NULL,
    description character varying(255),
    CONSTRAINT boxes_size_check CHECK (((size)::text = ANY ((ARRAY['S'::character varying, 'M'::character varying, 'L'::character varying])::text[])))
);


ALTER TABLE public.boxes OWNER TO bagpoint_sk_user;

--
-- Name: boxes_box_id_seq; Type: SEQUENCE; Schema: public; Owner: bagpoint_sk_user
--

CREATE SEQUENCE public.boxes_box_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.boxes_box_id_seq OWNER TO bagpoint_sk_user;

--
-- Name: boxes_box_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: bagpoint_sk_user
--

ALTER SEQUENCE public.boxes_box_id_seq OWNED BY public.boxes.box_id;


--
-- Name: messages; Type: TABLE; Schema: public; Owner: bagpoint_sk_user
--

CREATE TABLE public.messages (
    message_id integer NOT NULL,
    name character varying(30),
    email character varying(50),
    message text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.messages OWNER TO bagpoint_sk_user;

--
-- Name: messages_message_id_seq; Type: SEQUENCE; Schema: public; Owner: bagpoint_sk_user
--

CREATE SEQUENCE public.messages_message_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.messages_message_id_seq OWNER TO bagpoint_sk_user;

--
-- Name: messages_message_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: bagpoint_sk_user
--

ALTER SEQUENCE public.messages_message_id_seq OWNED BY public.messages.message_id;


--
-- Name: payments; Type: TABLE; Schema: public; Owner: bagpoint_sk_user
--

CREATE TABLE public.payments (
    payment_id integer NOT NULL,
    reservation_id integer NOT NULL,
    amount numeric(10,2) NOT NULL,
    payment_date timestamp without time zone NOT NULL,
    payment_method character varying(20) NOT NULL,
    payment_status character varying(20) NOT NULL,
    CONSTRAINT payments_payment_method_check CHECK (((payment_method)::text = ANY ((ARRAY['transfer'::character varying, 'card'::character varying])::text[]))),
    CONSTRAINT payments_payment_status_check CHECK (((payment_status)::text = ANY ((ARRAY['pending'::character varying, 'paid'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE public.payments OWNER TO bagpoint_sk_user;

--
-- Name: payments_payment_id_seq; Type: SEQUENCE; Schema: public; Owner: bagpoint_sk_user
--

CREATE SEQUENCE public.payments_payment_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payments_payment_id_seq OWNER TO bagpoint_sk_user;

--
-- Name: payments_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: bagpoint_sk_user
--

ALTER SEQUENCE public.payments_payment_id_seq OWNED BY public.payments.payment_id;


--
-- Name: reservation_boxes; Type: TABLE; Schema: public; Owner: bagpoint_sk_user
--

CREATE TABLE public.reservation_boxes (
    reservation_id integer NOT NULL,
    box_id integer NOT NULL
);


ALTER TABLE public.reservation_boxes OWNER TO bagpoint_sk_user;

--
-- Name: reservations; Type: TABLE; Schema: public; Owner: bagpoint_sk_user
--

CREATE TABLE public.reservations (
    reservation_id integer NOT NULL,
    users_user_id integer NOT NULL,
    total_price numeric(10,2) NOT NULL,
    reservation_date timestamp without time zone NOT NULL,
    status character varying(20) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT reservations_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'confirmed'::character varying, 'cancelled'::character varying, 'completed'::character varying])::text[])))
);


ALTER TABLE public.reservations OWNER TO bagpoint_sk_user;

--
-- Name: reservations_reservation_id_seq; Type: SEQUENCE; Schema: public; Owner: bagpoint_sk_user
--

CREATE SEQUENCE public.reservations_reservation_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reservations_reservation_id_seq OWNER TO bagpoint_sk_user;

--
-- Name: reservations_reservation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: bagpoint_sk_user
--

ALTER SEQUENCE public.reservations_reservation_id_seq OWNED BY public.reservations.reservation_id;


--
-- Name: reset_codes; Type: TABLE; Schema: public; Owner: bagpoint_sk_user
--

CREATE TABLE public.reset_codes (
    id integer NOT NULL,
    email character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    expires_at timestamp without time zone DEFAULT (CURRENT_TIMESTAMP + '01:00:00'::interval)
);


ALTER TABLE public.reset_codes OWNER TO bagpoint_sk_user;

--
-- Name: reset_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: bagpoint_sk_user
--

CREATE SEQUENCE public.reset_codes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reset_codes_id_seq OWNER TO bagpoint_sk_user;

--
-- Name: reset_codes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: bagpoint_sk_user
--

ALTER SEQUENCE public.reset_codes_id_seq OWNED BY public.reset_codes.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: bagpoint_sk_user
--

CREATE TABLE public.users (
    user_id integer NOT NULL,
    name character varying(50) NOT NULL,
    email character varying(100) NOT NULL,
    password character varying(255) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.users OWNER TO bagpoint_sk_user;

--
-- Name: users_user_id_seq; Type: SEQUENCE; Schema: public; Owner: bagpoint_sk_user
--

CREATE SEQUENCE public.users_user_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_user_id_seq OWNER TO bagpoint_sk_user;

--
-- Name: users_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: bagpoint_sk_user
--

ALTER SEQUENCE public.users_user_id_seq OWNED BY public.users.user_id;


--
-- Name: boxes box_id; Type: DEFAULT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.boxes ALTER COLUMN box_id SET DEFAULT nextval('public.boxes_box_id_seq'::regclass);


--
-- Name: messages message_id; Type: DEFAULT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.messages ALTER COLUMN message_id SET DEFAULT nextval('public.messages_message_id_seq'::regclass);


--
-- Name: payments payment_id; Type: DEFAULT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.payments ALTER COLUMN payment_id SET DEFAULT nextval('public.payments_payment_id_seq'::regclass);


--
-- Name: reservations reservation_id; Type: DEFAULT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.reservations ALTER COLUMN reservation_id SET DEFAULT nextval('public.reservations_reservation_id_seq'::regclass);


--
-- Name: reset_codes id; Type: DEFAULT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.reset_codes ALTER COLUMN id SET DEFAULT nextval('public.reset_codes_id_seq'::regclass);


--
-- Name: users user_id; Type: DEFAULT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.users ALTER COLUMN user_id SET DEFAULT nextval('public.users_user_id_seq'::regclass);


--
-- Data for Name: boxes; Type: TABLE DATA; Schema: public; Owner: bagpoint_sk_user
--

COPY public.boxes (box_id, box_name, size, location, price_per_day, status, description) FROM stdin;
\.


--
-- Data for Name: messages; Type: TABLE DATA; Schema: public; Owner: bagpoint_sk_user
--

COPY public.messages (message_id, name, email, message, created_at) FROM stdin;
\.


--
-- Data for Name: payments; Type: TABLE DATA; Schema: public; Owner: bagpoint_sk_user
--

COPY public.payments (payment_id, reservation_id, amount, payment_date, payment_method, payment_status) FROM stdin;
\.


--
-- Data for Name: reservation_boxes; Type: TABLE DATA; Schema: public; Owner: bagpoint_sk_user
--

COPY public.reservation_boxes (reservation_id, box_id) FROM stdin;
\.


--
-- Data for Name: reservations; Type: TABLE DATA; Schema: public; Owner: bagpoint_sk_user
--

COPY public.reservations (reservation_id, users_user_id, total_price, reservation_date, status, created_at) FROM stdin;
\.


--
-- Data for Name: reset_codes; Type: TABLE DATA; Schema: public; Owner: bagpoint_sk_user
--

COPY public.reset_codes (id, email, code, created_at, expires_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: bagpoint_sk_user
--

COPY public.users (user_id, name, email, password, created_at) FROM stdin;
2	Tami	tami.nemethova.t@gmail.com	$2y$12$rcsV5T9noGNyt001qjUes.VXX0ppUGligmtH9SfdYum4THQTEUFum	2025-11-01 18:37:08.478842
3	igorko	gg@gmail.com	$2y$12$lEIJqnLk8JbVzzU/bJqmhuX4ru5AzGgwNJaI32OFIq5Tn5fEJpwXO	2025-11-01 20:27:52.080619
1	igor	ig@gmail.com	$2y$12$c2gIEGkrNstn7XflOYjpfuBdOiXffSVaIpJDxDmhH7/5v7XrFDWLu	2025-11-01 16:09:18.639186
4	andrea	andula.dea@gmail.com	$2y$12$EQ3qte34EWoeThp/U72fsOuJSoGOkBjnqFuhfDZqKLMkOeJ7JwavS	2025-11-03 18:33:39.668797
\.


--
-- Name: boxes_box_id_seq; Type: SEQUENCE SET; Schema: public; Owner: bagpoint_sk_user
--

SELECT pg_catalog.setval('public.boxes_box_id_seq', 1, false);


--
-- Name: messages_message_id_seq; Type: SEQUENCE SET; Schema: public; Owner: bagpoint_sk_user
--

SELECT pg_catalog.setval('public.messages_message_id_seq', 1, false);


--
-- Name: payments_payment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: bagpoint_sk_user
--

SELECT pg_catalog.setval('public.payments_payment_id_seq', 1, false);


--
-- Name: reservations_reservation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: bagpoint_sk_user
--

SELECT pg_catalog.setval('public.reservations_reservation_id_seq', 1, false);


--
-- Name: reset_codes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: bagpoint_sk_user
--

SELECT pg_catalog.setval('public.reset_codes_id_seq', 3, true);


--
-- Name: users_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: bagpoint_sk_user
--

SELECT pg_catalog.setval('public.users_user_id_seq', 4, true);


--
-- Name: boxes boxes_pkey; Type: CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.boxes
    ADD CONSTRAINT boxes_pkey PRIMARY KEY (box_id);


--
-- Name: messages messages_pkey; Type: CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (message_id);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (payment_id);


--
-- Name: reservation_boxes reservation_boxes_pkey; Type: CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.reservation_boxes
    ADD CONSTRAINT reservation_boxes_pkey PRIMARY KEY (reservation_id, box_id);


--
-- Name: reservations reservations_pkey; Type: CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_pkey PRIMARY KEY (reservation_id);


--
-- Name: reset_codes reset_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.reset_codes
    ADD CONSTRAINT reset_codes_pkey PRIMARY KEY (id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- Name: payments payments_reservation_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_reservation_id_fkey FOREIGN KEY (reservation_id) REFERENCES public.reservations(reservation_id);


--
-- Name: reservation_boxes reservation_boxes_box_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.reservation_boxes
    ADD CONSTRAINT reservation_boxes_box_id_fkey FOREIGN KEY (box_id) REFERENCES public.boxes(box_id) ON DELETE CASCADE;


--
-- Name: reservation_boxes reservation_boxes_reservation_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.reservation_boxes
    ADD CONSTRAINT reservation_boxes_reservation_id_fkey FOREIGN KEY (reservation_id) REFERENCES public.reservations(reservation_id) ON DELETE CASCADE;


--
-- Name: reservations reservations_users_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: bagpoint_sk_user
--

ALTER TABLE ONLY public.reservations
    ADD CONSTRAINT reservations_users_user_id_fkey FOREIGN KEY (users_user_id) REFERENCES public.users(user_id);


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: -; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres GRANT ALL ON SEQUENCES TO bagpoint_sk_user;


--
-- Name: DEFAULT PRIVILEGES FOR TYPES; Type: DEFAULT ACL; Schema: -; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres GRANT ALL ON TYPES TO bagpoint_sk_user;


--
-- Name: DEFAULT PRIVILEGES FOR FUNCTIONS; Type: DEFAULT ACL; Schema: -; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres GRANT ALL ON FUNCTIONS TO bagpoint_sk_user;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: -; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres GRANT ALL ON TABLES TO bagpoint_sk_user;


--
-- PostgreSQL database dump complete
--

\unrestrict uQqhcue5ScAu1Y7t4SACydCfQKStuXGfyDrxtF0vZDodLg0pAVaopdd0WtyaMV1

