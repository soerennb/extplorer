from pyftpdlib.authorizers import DummyAuthorizer
from pyftpdlib.handlers import TLS_FTPHandler
from pyftpdlib.servers import FTPServer


class FixtureHandler(TLS_FTPHandler):
    certfile = '/certificates/server.pem'
    keyfile = '/certificates/server.key'
    passive_ports = range(30000, 30010)


authorizer = DummyAuthorizer()
authorizer.add_user('fixture', 'fixture-password', '/home/fixture/data', perm='elradfmwMT')
FixtureHandler.authorizer = authorizer
FTPServer(('0.0.0.0', 21), FixtureHandler).serve_forever()
